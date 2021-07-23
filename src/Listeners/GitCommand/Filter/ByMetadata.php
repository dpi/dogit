<?php

declare(strict_types=1);

namespace dogit\Listeners\GitCommand\Filter;

use dogit\DrupalOrg\Objects\DrupalOrgPatch;
use dogit\Events\GitCommand\FilterEvent;

/**
 * Filters patches by metadata.
 */
final class ByMetadata
{
    public function __invoke(FilterEvent $event): void
    {
        $event->logger->info('Filtering patches by patch metadata.');

        $logger = $event->logger;

        // Compute confidence upfront so these are all logged together.
        $event->filter(function (DrupalOrgPatch $patch) use ($logger): bool {
            $remove = true;

            // Some unintelligent detection.
            if (str_contains($patch->getUrl(), 'interdiff')) {
                $remove = false;
                $logger->debug('Comment #{comment_id}: {patch_url} looks like an interdiff', [
                    'comment_id' => $patch->getParent()->getSequence(),
                    'patch_url' => $patch->getUrl(),
                    'version' => $patch->getVersion(),
                ]);
            }
            if ((str_contains($patch->getUrl(), 'test-only')) || str_contains($patch->getUrl(), 'testonly')) {
                $remove = false;
                $logger->debug('Comment #{comment_id}: {patch_url} looks like a test only patch', [
                    'comment_id' => $patch->getParent()->getSequence(),
                    'patch_url' => $patch->getUrl(),
                    'version' => $patch->getVersion(),
                ]);
            }

            return $remove;
        });
    }
}
