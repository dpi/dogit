<?php

declare(strict_types=1);

namespace dogit\Events\GitCommand;

use dogit\DrupalOrg\DrupalOrgObjectIterator;
use Psr\Log\LoggerInterface;

final class VersionEvent extends DogitEvent
{
    /**
     * @param \dogit\DrupalOrg\Objects\DrupalOrgPatch[] $patches
     * @param \dogit\DrupalOrg\IssueGraph\Events\IssueEventInterface[] $issueEvents
     */
    public function __construct(
        public array $patches,
        public array $issueEvents,
        public DrupalOrgObjectIterator $objectIterator,
        public LoggerInterface $logger,
    ) {
    }
}
