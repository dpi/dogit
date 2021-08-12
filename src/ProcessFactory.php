<?php

declare(strict_types=1);

namespace dogit;

use Symfony\Component\Process\Process;

class ProcessFactory
{
    /**
     * @param string[] $command
     */
    public function createProcess(array $command, string $workingDirectory): Process
    {
        return new Process($command, $workingDirectory);
    }
}
