<?php

declare(strict_types=1);

namespace dogit\Events\PatchToBranch;

use dogit\Commands\Options\PatchToBranchOptions;
use Psr\Log\LoggerInterface;

final class FilterEvent extends AbstractFilterEvent
{
    /**
     * @param \dogit\DrupalOrg\Objects\DrupalOrgPatch[] $patches
     * @param \dogit\DrupalOrg\IssueGraph\Events\IssueEventInterface[] $issueEvents
     */
    public function __construct(public array $patches, public array $issueEvents, public LoggerInterface $logger, public PatchToBranchOptions $options)
    {
    }
}
