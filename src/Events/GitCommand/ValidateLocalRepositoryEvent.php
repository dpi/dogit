<?php

declare(strict_types=1);

namespace dogit\Events\GitCommand;

use dogit\Commands\Options\GitCommandOptions;
use dogit\Git\GitOperator;
use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Log\LoggerInterface;

final class ValidateLocalRepositoryEvent extends DogitEvent implements StoppableEventInterface
{
    public GitCommandOptions $options;

    public LoggerInterface $logger;

    public string $gitDirectory;

    public GitOperator $gitIo;

    private bool $stop = false;

    public function __construct(
        GitOperator $gitIo,
        string $gitDirectory,
        LoggerInterface $logger,
        GitCommandOptions $options
    ) {
        $this->gitIo = $gitIo;
        $this->gitDirectory = $gitDirectory;
        $this->logger = $logger;
        $this->options = $options;
    }

    /**
     * @return $this
     */
    public function setIsInvalid(): self
    {
        $this->stop = true;

        return $this;
    }

    public function isPropagationStopped(): bool
    {
        return $this->stop;
    }
}
