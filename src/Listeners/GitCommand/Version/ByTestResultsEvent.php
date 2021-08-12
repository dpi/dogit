<?php

declare(strict_types=1);

namespace dogit\Listeners\GitCommand\Version;

use Composer\Semver\Semver;
use dogit\DrupalOrg\IssueGraph\Events\IssueEventInterface;
use dogit\DrupalOrg\IssueGraph\Events\TestResultEvent;
use dogit\Events\GitCommand\VersionEvent;
use dogit\Utility;

final class ByTestResultsEvent
{
    public function __invoke(VersionEvent $event): void
    {
        $event->logger->debug('Checking patch versions by test result version.');

        // Interpret all versions upfront then quit afterwards if there are any failures.
        $errors = 0;

        // Resolve suspicious versions.
        // Where we resolved a comment having a version in the timeline, but
        // we detect a differing version from test results.
        foreach ($event->patches as $patch) {
            // Get tests results for this comment.
            $testResults = array_filter(
                $event->issueEvents,
                fn (IssueEventInterface $event): bool => $event instanceof TestResultEvent && ($event->getComment()->id() == $patch->getParent()->id()) && !empty($event->version()),
            );

            $message = '[Untested]';
            if (count($testResults) > 0) {
                // Determine whether the patch version derived from issue version can be found in the test results.
                try {
                    $satisfied = count(array_filter(
                        $testResults,
                        fn (TestResultEvent $testResult) => Semver::satisfies(
                            Utility::normalizeSemverVersion($testResult->version()) . '-dev',
                            $patch->getVersion() . '-dev',
                        ),
                    )) > 0;
                } catch (\UnexpectedValueException $e) {
                    ++$errors;
                    $event->logger->debug('Comment #{comment_id}: failed to interpret version for {patch_url}: Failed to interpret version: {message}', [
                        'comment_id' => $patch->getParent()->getSequence(),
                        'patch_url' => $patch->getUrl(),
                        'message' => $e->getMessage(),
                    ]);
                    continue;
                }

                $testResultVersions = [];
                if (!$satisfied) {
                    $testResultVersions = array_map(
                        fn (TestResultEvent $event) => $event->version(),
                        $testResults,
                    );
                    // Pick the first version in test results instead.
                    $guessedVersion = reset($testResultVersions);
                    $patch->setVersion($guessedVersion);
                }
                $message = $satisfied ? '' : sprintf("May be reroll for older, detected as '%s'", implode(', ', $testResultVersions));
            }

            $event->logger->debug('Comment #{comment_id}: {patch_url} guessed as {version} ({git_reference}) {message}', [
                'comment_id' => $patch->getParent()->getSequence(),
                'patch_url' => $patch->getUrl(),
                'version' => $patch->getVersion(),
                'git_reference' => $patch->getGitReference(),
                'message' => !empty($message) ? ": $message" : '',
            ]);
        }

        if ($errors > 0) {
            $event->setFailure();
        }
    }
}
