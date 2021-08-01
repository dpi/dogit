<?php

declare(strict_types=1);

namespace dogit\Listeners\PatchToBranch\Filter;

use dogit\DrupalOrg\Objects\DrupalOrgPatch;
use dogit\Events\PatchToBranch\FilterEvent;
use dogit\Utility;

/**
 * Allows excluding patches by associated comment sequence identifier.
 */
final class ByExcludedCommentOption
{
    public function __invoke(FilterEvent $event): void
    {
        $event->logger->info('Filtering patches by excluded comments options.');

        try {
            $filters = Utility::numericConstraintRuleBuilder($event->options->excludeComments);
        } catch (\InvalidArgumentException $e) {
            $event->logger->error(sprintf('Failed to process numeric constraints: %s', $e->getMessage()));
            $event->setFailure();

            return;
        }

        // Remove patches for excluded comments.
        $event->filter(function (DrupalOrgPatch $patch) use ($filters, $event): bool {
            foreach ($filters as $filter) {
                // If the callback matches then exclude.
                $sequence = $patch->getParent()->getSequence();
                if (true === $filter($sequence)) {
                    $event->logger->debug(sprintf('Removed patches for comment #%s since it matched an exclusion constraint.', $sequence));

                    return false;
                }
            }

            return true;
        });
    }
}
