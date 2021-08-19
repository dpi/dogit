<?php

declare(strict_types=1);

namespace dogit\tests\Commands;

use CzProject\GitPhp\Git;
use CzProject\GitPhp\IRunner;
use dogit\Commands\Options\ProjectCloneCommandOptions;
use dogit\Commands\ProjectCloneCommand;
use dogit\tests\DogitGuzzleTestMiddleware;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Finder\Finder;

/**
 * @coversDefaultClass \dogit\Commands\ProjectCloneCommand
 */
final class ProjectCloneCommandTest extends TestCase
{
    /**
     * @covers ::execute
     * @covers ::__construct
     */
    public function testCommand(): void
    {
        $testRepoDir = '/tmp/dogit-testing/fakedir';

        $git = $this->getMockBuilder(Git::class)
            ->getMock();
        $git->expects($this->once())
            ->method('cloneRepository')
            ->with('git@git.drupal.org:project/foo_bar_baz.git', $testRepoDir, []);

        $runner = $this->getMockBuilder(IRunner::class)->getMock();

        $command = $this->getMockBuilder(ProjectCloneCommand::class)
            ->onlyMethods(['git'])
            ->disableOriginalConstructor()
            ->getMock();
        $command->expects($this->once())
            ->method('git')
            ->willReturn($git);
        $finder = $this->createMock(Finder::class);
        $command->__construct($runner, $finder);

        $command->handlerStack()->push(new DogitGuzzleTestMiddleware());
        $tester = new CommandTester($command);

        $result = $tester->execute([
            ProjectCloneCommandOptions::ARGUMENT_PROJECT => 'foo_bar_baz',
            ProjectCloneCommandOptions::ARGUMENT_DIRECTORY => $testRepoDir,
        ]);
        $this->assertEquals(0, $result);

        $this->assertStringContainsString('[OK] Done', $tester->getDisplay());
    }

    public function testCommandNoDirectory(): void
    {
        $git = $this->getMockBuilder(Git::class)
            ->getMock();
        $git->expects($this->once())
            ->method('cloneRepository')
            // Expect second arg to be the same directory name as the project name.
            ->with('git@git.drupal.org:project/foo_bar_baz.git', 'foo_bar_baz', []);

        $runner = $this->getMockBuilder(IRunner::class)->getMock();

        $command = $this->getMockBuilder(ProjectCloneCommand::class)
            ->onlyMethods(['git'])
            ->disableOriginalConstructor()
            ->getMock();
        $command->expects($this->once())
            ->method('git')
            ->willReturn($git);
        $finder = $this->createMock(Finder::class);
        $command->__construct($runner, $finder);

        $command->handlerStack()->push(new DogitGuzzleTestMiddleware());
        $tester = new CommandTester($command);

        $result = $tester->execute([
            ProjectCloneCommandOptions::ARGUMENT_PROJECT => 'foo_bar_baz',
        ]);
        $this->assertEquals(0, $result);

        $this->assertStringContainsString('[OK] Done', $tester->getDisplay());
    }
}
