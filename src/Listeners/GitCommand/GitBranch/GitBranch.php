<?php

declare(strict_types=1);

namespace dogit\Listeners\GitCommand\GitBranch;

use dogit\Events\GitCommand\GitBranchEvent;

final class GitBranch
{
    public function __invoke(GitBranchEvent $event): void
    {
        $branchName = $event->options->branchName;

        if (empty($branchName)) {
            $branchName = 'dogit-' . $event->issue->id() . '-' . $event->initialGitReference;
        }

        $deleteBranchName = null;
        if ($event->gitIo->branchExists($branchName)) {
            if ($event->options->branchDeleteExisting) {
                // Rename branch in case it's checked out
                $event->logger->debug('Renaming existing branch {branch_name} so it can be deleted after a new branch with the same name is created.', [
                    'branch_name' => $branchName,
                ]);
                $deleteBranchName = $this->branchToDeleteSuffix($branchName);
                $event->gitIo->renameBranch($deleteBranchName, $branchName);
            } else {
                $event->logger
                    ->error(sprintf('Git branch %s already exists from a previous run. Specify a unique branch name with --branch or use --delete-existing-branch.', $branchName));
                $event->setFailure();

                return;
            }
        }

        $event->logger->info(sprintf('Starting branch at %s', $event->initialGitReference));
        $event->gitIo->clean();
        $event->gitIo->checkoutNew($branchName, 'origin/' . $event->initialGitReference);
        $event->logger->info('Checked out branch: ' . $branchName);

        if ($deleteBranchName) {
            $event->logger->info('Deleting old branch {branch_name}', [
                'branch_name' => $deleteBranchName,
            ]);
            $event->gitIo->deleteBranch($deleteBranchName);
        }
    }

    private function branchToDeleteSuffix(string $branchName): string
    {
        return $branchName . '-to-delete-' . (string) time();
    }
}
