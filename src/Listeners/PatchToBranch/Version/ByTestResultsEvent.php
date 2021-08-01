<?php

declare(strict_types=1);

namespace dogit\Listeners\PatchToBranch\Version;

use Composer\Semver\Semver;
use dogit\DrupalOrg\IssueGraph\Events\IssueEventInterface;
use dogit\DrupalOrg\IssueGraph\Events\TestResultEvent;
use dogit\Events\PatchToBranch\VersionEvent;

final class ByTestResultsEvent
{
    public function __invoke(VersionEvent $event): void
    {
        $event->logger->debug('Checking patch versions by test result version.');

        // Resolve suspicious versions.
        // Where we resolved a comment having a version in the timeline, but
        // we detect a differing version from test results.
        foreach ($event->patches as $patch) {
            // Get tests results for this comment.
            $testResults = array_filter(
                $event->issueEvents,
                fn (IssueEventInterface $event): bool => $event instanceof TestResultEvent && $event->getComment()->id() == $patch->getParent()->id(),
            );

            $message = '[Untested]';
            if (count($testResults) > 0) {
                // Determine whether the patch version derived from issue version can be found in the test results.
                $satisfied = count(array_filter(
                    $testResults,
                    fn (TestResultEvent $event) => Semver::satisfies($event->version() . '-dev', $patch->getVersion() . '-dev'),
                )) > 0;

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

            $event->logger->debug('Comment #{comment_id}: {patch_url} guessed as {version}{message}', [
                'comment_id' => $patch->getParent()->getSequence(),
                'patch_url' => $patch->getUrl(),
                'version' => $patch->getVersion(),
                'message' => !empty($message) ? ": $message" : '',
            ]);
        }
    }
}
