<?php

declare(strict_types=1);

namespace dogit\Listeners\PatchToBranch\FilterByResponse;

use dogit\DrupalOrg\Objects\DrupalOrgPatch;
use dogit\Events\PatchToBranch\FilterByResponseEvent;

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

            try {
                $contents = $patch->getContents();
            } catch (\LogicException $e) {
                throw new \LogicException(sprintf('Missing patch contents for patch #%s %s', $patch->getParent()->getSequence(), $patch->getUrl()), 0, $e);
            }

            if (null === $contents || 0 === strlen($contents)) {
                $logger->debug('Removed empty patch #{comment_id} {patch_url}.', [
                    'comment_id' => $patch->getParent()->getSequence(),
                    'patch_url' => $patch->getUrl(),
                ]);

                return false;
            }

            return true;
        });
    }
}
