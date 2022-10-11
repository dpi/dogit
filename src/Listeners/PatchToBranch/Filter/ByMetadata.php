<?php

declare(strict_types=1);

namespace dogit\Listeners\PatchToBranch\Filter;

use dogit\DrupalOrg\IssueGraph\Events\IssueEventInterface;
use dogit\DrupalOrg\IssueGraph\Events\TestResultEvent;
use dogit\DrupalOrg\Objects\DrupalOrgPatch;
use dogit\Events\PatchToBranch\FilterEvent;

/**
 * Filters patches by metadata.
 */
final class ByMetadata
{
    public function __invoke(FilterEvent $event): void
    {
        $event->logger->info('Filtering patches by patch metadata.');

        $logger = $event->logger;
        $issueEvents = $event->issueEvents;

        // Compute confidence upfront so these are all logged together.
        $event->filter(function (DrupalOrgPatch $patch) use ($logger, $issueEvents): bool {
            // Get tests results for this comment.
            /** @var \dogit\DrupalOrg\IssueGraph\Events\TestResultEvent[] $testResults */
            $testResults = array_filter(
                $issueEvents,
                fn (IssueEventInterface $event): bool => $event instanceof TestResultEvent && $event->getComment()->id() == $patch->getParent()->id(),
            );

            $keep = true;

            foreach ($testResults as $testResult) {
                if (str_contains($testResult->result(), 'Unable to apply patch')) {
                    $keep = false;
                    $logger->debug('Comment #{comment_id}: {patch_url} failed to apply during test run.', [
                        'comment_id' => $patch->getParent()->getSequence(),
                        'patch_url' => $patch->getUrl(),
                    ]);
                }
            }

            if (str_contains($patch->getUrl(), 'interdiff')) {
                $keep = false;
                $logger->debug('Comment #{comment_id}: {patch_url} looks like an interdiff', [
                    'comment_id' => $patch->getParent()->getSequence(),
                    'patch_url' => $patch->getUrl(),
                ]);
            }

            if (str_contains($patch->getUrl(), 'test-only') || str_contains($patch->getUrl(), 'testonly')) {
                $keep = false;
                $logger->debug('Comment #{comment_id}: {patch_url} looks like a test only patch', [
                    'comment_id' => $patch->getParent()->getSequence(),
                    'patch_url' => $patch->getUrl(),
                ]);
            }

            return $keep;
        });
    }
}
