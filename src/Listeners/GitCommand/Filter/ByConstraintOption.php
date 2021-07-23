<?php

declare(strict_types=1);

namespace dogit\Listeners\GitCommand\Filter;

use Composer\Semver\Semver;
use dogit\DrupalOrg\Objects\DrupalOrgPatch;
use dogit\Events\GitCommand\FilterEvent;

/**
 * Allows filtering by detected version.
 */
final class ByConstraintOption
{
    public function __invoke(FilterEvent $event): void
    {
        $event->logger->info('Filtering patches by constraint argument.');

        $versionConstraints = $event->options->versionConstraints;
        if (!empty($versionConstraints)) {
            $event->filter(fn (DrupalOrgPatch $patch): bool => Semver::satisfies(
                str_replace('.x', '.9999', $patch->getVersion()),
                $versionConstraints,
            ));
        }
    }
}
