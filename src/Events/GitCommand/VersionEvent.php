<?php

declare(strict_types=1);

namespace dogit\Events\GitCommand;

use dogit\DrupalOrg\DrupalOrgObjectIterator;
use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Log\LoggerInterface;

final class VersionEvent extends DogitEvent implements StoppableEventInterface
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
        protected bool $failure = false,
    ) {
    }

    /**
     * @return $this
     */
    public function setFailure(): self
    {
        $this->failure = true;

        return $this;
    }

    public function isPropagationStopped(): bool
    {
        return $this->failure;
    }
}
