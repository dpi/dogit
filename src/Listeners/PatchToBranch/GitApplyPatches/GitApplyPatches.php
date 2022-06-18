<?php

declare(strict_types=1);

namespace dogit\Listeners\PatchToBranch\GitApplyPatches;

use dogit\DrupalOrg\Objects\DrupalOrgPatch;
use dogit\Events\PatchToBranch\GitApplyPatchesEvent;
use dogit\Git\GitResolver;
use dogit\ProcessFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\InputStream;

final class GitApplyPatches
{
    public function __invoke(GitApplyPatchesEvent $event): void
    {
        $input = $event->input;
        $output = $event->output;
        $patches = $event->patches;
        $gitDirectory = $event->gitIo->getRepositoryPath();
        $issue = $event->issue;
        $gitIo = $event->gitIo;
        $excludeComments = $event->options->excludeComments;
        $patchLevel = $event->options->patchLevel;
        $linearMode = $event->linearMode;

        // Resolve hashes.
        /** @var array<array{\dogit\DrupalOrg\Objects\DrupalOrgPatch, string}> $hashes */
        $hashes = array_map(fn (DrupalOrgPatch $patch): array => [
            $patch,
            (new GitResolver($patch, $gitIo))->getHash(),
        ], $patches);

        if (0 === count($hashes)) {
            $output->writeln('No patches found.');
            $event->setFailure();

            return;
        }

        $firstHash = reset($hashes)[1];
        $gitIo->resetHard($firstHash);

        $firstHash = reset($hashes)[1];
        $filesAdded = [];
        $patchCommitHash = null;

        $sectionOutput = $output->section();
        $sectionPatchSingle = $output->section();
        $sectionPatchAll = $output->section();
        $patchAllProgressBar = new ProgressBar($sectionPatchAll, count($hashes));

        $io = new SymfonyStyle($input, $sectionOutput);
        $logger = new ConsoleLogger($io);

        $patchAllProgressBar->start();
        foreach ($hashes as [$patch, $hash]) {
            $patchContents = $patch->getContents();
            if (null === $patchContents || 0 === strlen($patchContents)) {
                // This should have been filtered by \dogit\Listeners\PatchToBranch\FilterByResponse\ByBody.
                $io->warning(sprintf('Patch for comment %s is empty. Skipping.', $patch->getParent()->getSequence()));

                continue;
            }

            $patchAllProgressBar->advance();
            $sectionPatchSingle->clear();
            $patchSingleProgressBar = new ProgressBar($sectionPatchSingle, 100);
            $patchSingleProgressBar->setMessage(sprintf('Patch for comment %s', $patch->getParent()->getSequence()));

            $contextString = !$io->isDebug() ? 'comment #{comment_id}' : 'comment #{comment_id} patch URL <href={patch_url}>{patch_url}</>';
            $contextArgs = [
                'comment_id' => $patch->getParent()->getSequence(),
                'patch_url' => $patch->getUrl(),
            ];

            // Merge and reset state to without the changes from last patch.
            if (null !== $patchCommitHash && strlen($patchCommitHash) > 0) {
                if (!$linearMode) {
                    $patchSingleProgressBar->setProgress(10);
                    $logger->info(sprintf('Merging into commit #%s before patch', substr($patchCommitHash, 0, 8)), $contextArgs);
                    $gitIo->mergeStrategyOurs($hash);

                    // Detect if the merge created a commit. Sometimes no commit is created, so dont amend in this case.
                    // This will happen when there is no intermediate commits in the repository between two uploaded
                    // patches.
                    if ($patchCommitHash !== $gitIo->getLastCommitId()) {
                        $patchSingleProgressBar->setProgress(20);
                        $gitIo->commit([
                            '--amend',
                            '--allow-empty',
                            ['--reuse-message', $gitIo->getLastCommitId()],
                            ['--date', $patch->getCreated()->getTimestamp()],
                            ['--author', 'dogit <dogit@dogit.dev>'],
                        ]);
                    } else {
                        $logger->info('Merge did not result in commits');
                    }

                    $patchSingleProgressBar->setProgress(30);
                    $gitIo->checkoutPathspec($hash, '.');
                } else {
                    $patchSingleProgressBar->setProgress(25);
                    $gitIo->checkoutPathspec($firstHash, '.');
                }

                // Remove files added by patch commits to fully restore the state of $hash.
                $this->deleteFiles($filesAdded, $gitDirectory, $event->processFactory);
            }

            $logger->info(sprintf('Applying patch for %s', $contextString), $contextArgs);
            $patchSingleProgressBar->setProgress(50);
            [$code, $output] = $this->applyPatch(
                $patchContents,
                $gitDirectory,
                $patchLevel,
                $event->processFactory,
            );

            if (Command::SUCCESS !== $code) {
                $sectionPatchSingle->clear();
                $sectionPatchAll->clear();

                $logger->error(sprintf('Failed to apply %s:', $contextString), $contextArgs);
                $io->note(sprintf(
                    'This may be caused by a bad patch, reroll, or a fault in the command. Check the comment on Drupal.org and consider omitting patches from this comment with --exclude (%s) flag. The rejected patch can be found in the Git working directory. It may not be possible to resolve this problem. Check the patch command output and look in the Git working directory for .rej files.',
                    implode(' ', array_map(
                        fn (string $excludeComment): string => '-e ' . $excludeComment,
                        array_merge($excludeComments, [(string) $patch->getParent()->getSequence()]),
                    ))
                ));
                $io->text(sprintf(
                    '<href=%s>Comment #%s by %s at %s</>',
                    $patch->getParent()->url(),
                    $patch->getParent()->getSequence(),
                    $patch->getParent()->getAuthorName(),
                    $patch->getParent()->getCreated()->format('r'),
                ));
                $io->block(
                    messages: strip_tags($patch->getParent()->getComment()),
                    style: 'fg=yellow',
                    prefix: ' > ',
                );
                $logger->error("Patch code {exit_code}, output follows:\n{output}", [
                    'exit_code' => $code,
                    'output' => $output,
                ]);

                // @todo prompt to continue: skip this patch
                // @todo placeholder commit on skip

                $event->setFailure();

                return;
            } else {
                $logger->info('Patch apply reported success');
            }

            $logger->debug('Staging all changes.');
            $patchSingleProgressBar->setProgress(75);
            $gitIo->addAllChanges();
            $patchAuthorName = $patch->getParent()->getAuthorName();

            $logger->debug('Committing patch');
            $patchSingleProgressBar->advance(90);
            $gitIo->commit([
                ['--date', $patch->getCreated()->getTimestamp() . ''],
                sprintf(
                    '--author=%s <%s@%s.no-reply.drupal.org>',
                    $patchAuthorName,
                    $patch->getParent()->getAuthorId(),
                    $patchAuthorName,
                ),
                '--allow-empty',
                ['--message', sprintf(
                    "Patch #%s on %s\n\nPatch URL: %s\nComment URL: %s\nIssue URL: %s\nPatch uploaded by %s\nCommit built with dogit.dev",
                    $patch->getParent()->getSequence(),
                    $patch->getGitReference(),
                    $patch->getUrl(),
                    $patch->getParent()->url(),
                    $issue->url(),
                    $patchAuthorName,
                )],
            ]);

            $patchCommitHash = $gitIo->getLastCommitId();

            $filesAdded = array_unique(array_merge($filesAdded, $gitIo->getNewFiles($patchCommitHash)));
            $patchSingleProgressBar->finish();
        }

        $patchAllProgressBar->finish();
        $sectionPatchSingle->clear();
        $sectionPatchAll->clear();
    }

    /**
     * @param string[] $files
     */
    private function deleteFiles(array $files, string $workingDirectory, ProcessFactory $processFactory): void
    {
        foreach ($files as $file) {
            $process = $processFactory->createProcess(['rm', $file], $workingDirectory);
            $process->run();
        }
    }

    /**
     * @return array{int, string}
     */
    private function applyPatch(string $data, string $workingDirectory, int $patchLevel, ProcessFactory $processFactory): array
    {
        $process = $processFactory->createProcess([
            'patch',
            sprintf('-p%d', $patchLevel),
        ], $workingDirectory);

        $input = new InputStream();
        $process->setInput($input);
        $process->start();

        $input->write($data);
        $input->close();

        $code = $process->wait();
        $output = $process->getOutput();

        return [$code, $output];
    }
}
