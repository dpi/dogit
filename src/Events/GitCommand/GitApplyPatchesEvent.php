<?php

declare(strict_types=1);

namespace dogit\Events\GitCommand;

use dogit\Commands\Options\GitCommandOptions;
use dogit\DrupalOrg\Objects\DrupalOrgIssue;
use dogit\Git\GitOperator;
use Psr\EventDispatcher\StoppableEventInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

final class GitApplyPatchesEvent extends DogitEvent implements StoppableEventInterface
{
    public bool $linearMode;

    public GitCommandOptions $options;

    public DrupalOrgIssue $issue;

    public ConsoleOutputInterface $output;

    public InputInterface $input;

    public GitOperator $gitIo;

    public array $patches;

    private bool $failure = false;

    /**
     * @param \dogit\DrupalOrg\Objects\DrupalOrgPatch[] $patches
     */
    public function __construct(
        array $patches,
        GitOperator $gitIo,
        InputInterface $input,
        ConsoleOutputInterface $output,
        DrupalOrgIssue $issue,
        GitCommandOptions $options,
        bool $linearMode
    ) {
        $this->patches = $patches;
        $this->gitIo = $gitIo;
        $this->input = $input;
        $this->output = $output;
        $this->issue = $issue;
        $this->options = $options;
        $this->linearMode = $linearMode;
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
