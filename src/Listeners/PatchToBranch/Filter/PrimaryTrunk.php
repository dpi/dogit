<?php

declare(strict_types=1);

namespace dogit\Listeners\PatchToBranch\Filter;

use Composer\Semver\Comparator;
use dogit\DrupalOrg\Objects\DrupalOrgPatch;
use dogit\Events\PatchToBranch\FilterEvent;

/**
 * Ensures patches ascend in associated version, removing patches with versions less than a high water mark.
 */
final class PrimaryTrunk
{
    public function __invoke(FilterEvent $event): void
    {
        $event->logger->info('Constructing trunk.');

        // @todo allow building non-linear tree.
        /** @var \dogit\DrupalOrg\Objects\DrupalOrgPatch[] $secondaries */
        $secondaries = [];

        // Compute Version high-water mark to figure out linear graph of
        // patches.
        /** @var string|null $versionHwm */
        $versionHwm = null;
        $event->filter(function (DrupalOrgPatch $patch) use (&$versionHwm, &$secondaries): bool {
            $patchVersion = $patch->getVersion();
            if (null !== $versionHwm && strlen($versionHwm) > 0 && Comparator::lessThan($patchVersion, $versionHwm)) {
                // Skip this one.
                $secondaries[] = $patch;

                return false;
            }

            $versionHwm = $patchVersion;

            return true;
        });
    }
}
