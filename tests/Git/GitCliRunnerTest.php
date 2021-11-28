<?php

declare(strict_types=1);

namespace dogit\tests\Git;

use CzProject\GitPhp\GitException;
use dogit\Git\CliRunner;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \dogit\Git\CliRunner
 */
final class GitCliRunnerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @covers ::run
     */
    public function testRun(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with('Executing command: {command}', [
                'command' => 'git foo hello world',
            ]);

        $cliRunner = new CliRunner(logger: $logger);
        $this->expectException(GitException::class);
        $this->expectExceptionMessage("Directory 'testcwd' not found");
        $cliRunner->run('testcwd', [
            'foo',
            ['hello' => 'world'],
        ]);
    }

    /**
     * @covers ::setLogger
     */
    public function testSetLogger(): void
    {
        $cliRunner = new CliRunner();
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with('Executing command: {command}', [
                'command' => 'git foo hello world',
            ]);

        $cliRunner->setLogger($logger);
        $this->expectException(GitException::class);
        $this->expectExceptionMessage("Directory 'testcwd' not found");
        $cliRunner->run('testcwd', [
            'foo',
            ['hello' => 'world'],
        ]);
    }
}
