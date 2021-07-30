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
    public string $initalVersion;

    public GitCommandOptions $options;

    public DrupalOrgIssue $issue;

    public LoggerInterface $logger;

    public GitOperator $gitIo;

    private bool $failure = false;

    public function __construct(
        GitOperator $gitIo,
        LoggerInterface $logger,
        DrupalOrgIssue $issue,
        GitCommandOptions $options,
        string $initalVersion
    ) {
        $this->gitIo = $gitIo;
        $this->logger = $logger;
        $this->issue = $issue;
        $this->options = $options;
        $this->initalVersion = $initalVersion;
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
