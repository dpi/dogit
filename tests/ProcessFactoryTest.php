<?php

declare(strict_types=1);

namespace dogit\tests;

use dogit\ProcessFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @coversDefaultClass \dogit\ProcessFactory
 */
final class ProcessFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @covers ::createProcess
     */
    public function testCreateProcess(): void
    {
        $processFactory = new ProcessFactory();
        $process = $processFactory->createProcess(['ls'], '/tmp/testdirectory');
        $this->assertEquals("'ls'", $process->getCommandLine());
        $this->assertEquals('/tmp/testdirectory', $process->getWorkingDirectory());
    }
}
