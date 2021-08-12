<?php

declare(strict_types=1);

namespace dogit\Events\GitCommand;

use dogit\Commands\Options\GitCommandOptions;
use dogit\DrupalOrg\Objects\DrupalOrgIssue;
use dogit\Git\GitOperator;
use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Log\LoggerInterface;

final class GitBranchEvent extends DogitEvent implements StoppableEventInterface
{
    private bool $failure = false;

    public function __construct(
        public GitOperator $gitIo,
        public LoggerInterface $logger,
        public DrupalOrgIssue $issue,
        public GitCommandOptions $options,
        public string $initialGitReference,
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
