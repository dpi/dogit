<?php

declare(strict_types=1);

namespace dogit\Events\GitCommand;

use dogit\Commands\Options\GitCommandOptions;
use Psr\Log\LoggerInterface;

final class FilterEvent extends AbstractFilterEvent
{
    /**
     * @param \dogit\DrupalOrg\Objects\DrupalOrgPatch[] $patches
     * @param \dogit\DrupalOrg\IssueGraph\Events\IssueEventInterface[] $issueEvents
     */
    public function __construct(public array $patches, public array $issueEvents, public LoggerInterface $logger, public GitCommandOptions $options, protected bool $failure = false)
    {
    }
}
