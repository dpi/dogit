<?php

declare(strict_types=1);

namespace dogit\Events\GitCommand;

use dogit\Commands\Options\GitCommandOptions;
use Psr\Log\LoggerInterface;

final class FilterEvent extends AbstractFilterEvent
{
    public function __construct(public array $patches, public LoggerInterface $logger, public GitCommandOptions $options, protected bool $failure = false)
    {
    }
}
