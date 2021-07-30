<?php

declare(strict_types=1);

namespace dogit\Events\GitCommand;

use dogit\DrupalOrg\DrupalOrgObjectIterator;
use Psr\Log\LoggerInterface;

final class VersionEvent extends DogitEvent
{

  public LoggerInterface $logger;

  public DrupalOrgObjectIterator $objectIterator;

  public array $issueEvents;

  public array $patches;

  /**
     * @param \dogit\DrupalOrg\Objects\DrupalOrgPatch[] $patches
     * @param \dogit\DrupalOrg\IssueGraph\Events\IssueEventInterface[] $issueEvents
     */
    public function __construct(
        array $patches,
        array $issueEvents,
        DrupalOrgObjectIterator $objectIterator,
        LoggerInterface $logger
    ) {
      $this->patches = $patches;
      $this->issueEvents = $issueEvents;
      $this->objectIterator = $objectIterator;
      $this->logger = $logger;
    }
}
