<?php

declare(strict_types=1);

namespace dogit\Events\GitCommand;

use dogit\Commands\Options\GitCommandOptions;
use dogit\DrupalOrg\Objects\DrupalOrgIssue;
use dogit\Git\GitOperator;
use dogit\ProcessFactory;
use Psr\EventDispatcher\StoppableEventInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

final class GitApplyPatchesEvent extends DogitEvent implements StoppableEventInterface
{
    private bool $failure = false;

    /**
     * @param \dogit\DrupalOrg\Objects\DrupalOrgPatch[] $patches
     */
    public function __construct(
        public array $patches,
        public GitOperator $gitIo,
        public InputInterface $input,
        public ConsoleOutputInterface $output,
        public DrupalOrgIssue $issue,
        public GitCommandOptions $options,
        public bool $linearMode,
        public ProcessFactory $processFactory,
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
