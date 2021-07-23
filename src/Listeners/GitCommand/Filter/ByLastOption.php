<?php

declare(strict_types=1);

namespace dogit\Listeners\GitCommand\Filter;

use dogit\DrupalOrg\Objects\DrupalOrgPatch;
use dogit\Events\GitCommand\FilterEvent;

/**
 * Removes all previously filtered patches leaving only the last patch.
 */
final class ByLastOption
{
    public function __invoke(FilterEvent $event): void
    {
        if (!$event->options->onlyLastPatch) {
            return;
        }

        $event->logger->info('Removing all patches except the last.');

        $patches = $event->getPatches();
        if (count($patches) <= 1) {
            return;
        }

        $lastPatch = array_pop($patches);
        $event->setPatches([$lastPatch]);

        $removedCommentIds = array_map(fn (DrupalOrgPatch $patch): int => $patch->getParent()->getSequence(), $patches);
        $event->logger->info(sprintf(
            'Filtered patches for comments %s leaving patch for comment %d.',
            implode(', ', $removedCommentIds),
            $lastPatch->getParent()->getSequence(),
        ));
    }
}
