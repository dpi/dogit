<?php

declare(strict_types=1);

namespace dogit\Listeners\PatchToBranch\Version;

use dogit\DrupalOrg\IssueGraph\Events\IssueEvent;
use dogit\Events\PatchToBranch\VersionEvent;
use dogit\Utility;

final class ByVersionChangeEvent
{
    public function __invoke(VersionEvent $event): void
    {
        $event->logger->debug('Checking patch version by time.');

        // Given a patches associated with comments, and events produced from an
        // issue graph, determine the issue version at the time each patch was
        // posted.
        $versionChangeEvents = IssueEvent::filterVersionChangeEvents($event->issueEvents);
        foreach ($event->patches as $patch) {
            // Version for patch at this point is the estimated version from the graph, which is in turn
            // based off issue version changes.
            $version = Utility::versionAt($event->objectIterator, $patch->getParent()->getCreated(), $versionChangeEvents);
            $patch
                ->setVersion(Utility::normalizeSemverVersion($version))
                ->setGitReference(Utility::normalizeGitReferenceVersion($version));
        }
    }
}
