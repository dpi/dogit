<?php

declare(strict_types=1);

namespace dogit\Git;

use CzProject\GitPhp\IRunner;
use Psr\Log\LoggerInterface;

/**
 * Interface for Git Runner.
 */
interface CliRunnerInterface extends IRunner
{
    public function setLogger(LoggerInterface $logger): void;
}
