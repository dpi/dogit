<?php

declare(strict_types=1);

namespace dogit\Events\GitCommand;

use dogit\Commands\Options\GitCommandOptions;
use Psr\Log\LoggerInterface;

final class FilterEvent extends AbstractFilterEvent
{
    public GitCommandOptions $options;

    public LoggerInterface $logger;

    public array $patches;

    protected bool $failure = false;

    public function __construct(array $patches, LoggerInterface $logger, GitCommandOptions $options, bool $failure = false)
    {
        $this->patches = $patches;
        $this->logger = $logger;
        $this->options = $options;
        $this->failure = $failure;
    }
}
