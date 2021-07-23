<?php

declare(strict_types=1);

namespace dogit\Listeners\GitCommand\FilterByResponse;

use dogit\DrupalOrg\Objects\DrupalOrgPatch;
use dogit\Events\GitCommand\FilterByResponseEvent;

/**
 * Filters patches by response contents.
 */
final class ByBody
{
    public function __invoke(FilterByResponseEvent $event): void
    {
        $logger = $event->logger;

        // Filter out patches based on patch download response.
        $event->filter(function (DrupalOrgPatch $patch) use ($logger): bool {
            // Skip empty, like 2350939-88
            if (empty($patch->getContents())) {
                $logger->debug('Removed empty patch #{comment_id} {patch_url}.', [
                    'comment_id' => $patch->getParent()->getSequence(),
                    'patch_url' => $patch->getUrl(),
                    'version' => $patch->getVersion(),
                ]);

                return false;
            }

            return true;
        });
    }
}
