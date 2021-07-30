<?php

declare(strict_types=1);

namespace dogit\Events\GitCommand;

use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractFilterEvent extends DogitEvent implements StoppableEventInterface
{

  public LoggerInterface $logger;

  protected array $patches;

  protected bool $failure = FALSE;

  /**
     * @param \dogit\DrupalOrg\Objects\DrupalOrgPatch[] $patches
     */
    public function __construct(array $patches, LoggerInterface $logger, bool $failure = false)
    {
      $this->failure = $failure;
      $this->logger = $logger;
      $this->patches = $patches;
    }

    /**
     * @return \dogit\DrupalOrg\Objects\DrupalOrgPatch[]
     */
    public function getPatches(): array
    {
        return $this->patches;
    }

    /**
     * @param \dogit\DrupalOrg\Objects\DrupalOrgPatch[] $patches
     *
     * @return $this
     */
    public function setPatches(array $patches): AbstractFilterEvent
    {
        $this->patches = $patches;

        return $this;
    }

    /**
     * @param callable $callback
     *   A callable to apply to each patch. If the callable returns FALSE
     *   then the patch will be removed.
     *
     * @return $this
     */
    public function filter(callable $callback): AbstractFilterEvent
    {
        $this->setPatches(array_filter(
            $this->getPatches(),
            $callback,
        ));

        return $this;
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
