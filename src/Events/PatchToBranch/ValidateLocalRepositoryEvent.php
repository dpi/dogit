<?php

declare(strict_types=1);

namespace dogit\Events\PatchToBranch;

use dogit\Commands\Options\PatchToBranchOptions;
use dogit\Git\GitOperator;
use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Log\LoggerInterface;

final class ValidateLocalRepositoryEvent extends DogitEvent implements StoppableEventInterface
{
    private bool $stop = false;

    public function __construct(
        public GitOperator $gitIo,
        public string $gitDirectory,
        public LoggerInterface $logger,
        public PatchToBranchOptions $options,
    ) {
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
