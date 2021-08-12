<?php

declare(strict_types=1);

namespace dogit\Listeners\PatchToBranch\Terminate;

use dogit\DrupalOrg\Objects\DrupalOrgComment;
use dogit\DrupalOrg\Objects\DrupalOrgFile;
use dogit\DrupalOrg\Objects\DrupalOrgIssue;
use dogit\DrupalOrg\Objects\DrupalOrgObject;
use dogit\DrupalOrg\Objects\DrupalOrgPatch;
use dogit\Events\PatchToBranch\TerminateEvent;

final class Statistics
{
    public function __invoke(TerminateEvent $event): void
    {
        if (!$event->io->isDebug()) {
            return;
        }

        $objectCounter = [];
        foreach ($event->repository->all() as $object) {
            assert($object instanceof DrupalOrgObject);
            $objectCounter[$object::class] = ($objectCounter[$object::class] ?? 0) + 1;
        }
        $event->io->definitionList(
            'Object statistics',
            ['Issues' => $objectCounter[DrupalOrgIssue::class] ?? 0],
            ['Comments' => $objectCounter[DrupalOrgComment::class] ?? 0],
            ['Files' => $objectCounter[DrupalOrgFile::class] ?? 0],
            ['Patches' => $objectCounter[DrupalOrgPatch::class] ?? 0],
        );
    }
}
