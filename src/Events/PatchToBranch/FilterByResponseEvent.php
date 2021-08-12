<?php

declare(strict_types=1);

namespace dogit\Events\PatchToBranch;

use Psr\Log\LoggerInterface;

final class FilterByResponseEvent extends AbstractFilterEvent
{
    /**
     * @param \dogit\DrupalOrg\Objects\DrupalOrgPatch[] $patches
     */
    public function __construct(public array $patches, public LoggerInterface $logger)
    {
    }
}
