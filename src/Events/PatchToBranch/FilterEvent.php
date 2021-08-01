<?php

declare(strict_types=1);

namespace dogit\Events\PatchToBranch;

use dogit\Commands\Options\PatchToBranchOptions;
use Psr\Log\LoggerInterface;

final class FilterEvent extends AbstractFilterEvent
{
    public function __construct(public array $patches, public LoggerInterface $logger, public PatchToBranchOptions $options, protected bool $failure = false)
    {
    }
}
