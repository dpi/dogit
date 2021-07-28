<?php

declare(strict_types=1);

namespace dogit\tests\Commands;

use CzProject\GitPhp\Git;
use CzProject\GitPhp\GitException;
use CzProject\GitPhp\GitRepository;
use CzProject\GitPhp\IRunner;
use CzProject\GitPhp\RunnerResult;
use dogit\Commands\Options\ProjectMergeRequestOptions;
use dogit\Commands\ProjectMergeRequest;
use dogit\tests\DogitGuzzleGitlabTestMiddleware;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @coversDefaultClass \dogit\Commands\ProjectMergeRequest
 */
final class ProjectMergeRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        putenv('COLUMNS=120');
    }

    /**
     * @covers ::execute
     */
    public function testCommand(): void
    {
        $testRepoDir = '/tmp/dogit-testing/fakedir';

        $git = $this->getMockBuilder(Git::class)
            ->getMock();
        $git->expects($this->once())
            ->method('open')
            ->with($testRepoDir)
            ->willThrowException(new GitException("Repository 'Blurgh' not found."));
        $git->expects($this->once())
            ->method('cloneRepository');

        $runner = $this->getMockBuilder(IRunner::class)
            ->getMock();

        $command = $this->getMockBuilder(ProjectMergeRequest::class)
            ->onlyMethods(['git'])
            ->disableOriginalConstructor()
            ->getMock();
        $command->expects($this->once())
            ->method('git')
            ->willReturn($git);
        $command->__construct($runner);

        $command->handlerStack()->push(new DogitGuzzleGitlabTestMiddleware());
        $tester = new CommandTester($command);
        $tester->setInputs([
            '8',
        ]);
        $result = $tester->execute([
            ProjectMergeRequestOptions::ARGUMENT_PROJECT => 'foo_bar_baz',
            ProjectMergeRequestOptions::ARGUMENT_DIRECTORY => $testRepoDir,
        ]);

        $this->assertEquals(0, $result);
        $this->assertStringContainsString('Found project foo_bar_baz with ID #13371337', $tester->getDisplay());
        $this->assertStringContainsString('Select merge request to checkout:', $tester->getDisplay());
        $this->assertStringContainsString('[9] Merge request !9: Issue #2866889: WSD on workflow module states page by dpi', $tester->getDisplay());
        $this->assertStringContainsString('[8] Merge request !8: Issue #2845094: Batch field creation by dpi', $tester->getDisplay());
        $this->assertStringContainsString('> 8', $tester->getDisplay());
        $this->assertStringContainsString('Checking out merge request !8: Issue #2845094: Batch field creation by dpi into directory /tmp/dogit-testing/fakedir', $tester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Interpreting directory `/tmp/dogit-testing/fakedir` as not a Git repository, cloning..', $tester->getDisplay());
        $this->assertStringContainsString('[OK] Done', $tester->getDisplay());
    }

    /**
     * @covers ::execute
     */
    public function testCommandExistingGitDirectory(): void
    {
        $testRepoDir = '/tmp/dogit-testing/fakedir';

        $repo = $this->createMock(GitRepository::class);
        $repo->expects($this->exactly(5))
            ->method('execute')
            ->withConsecutive(
                [['rev-parse', '--verify', '--quiet', 'my-cool-branch-name']],
                ['remote'],
                ['remote', 'get-url', 'foo-remote'],
                ['remote', 'get-url', 'bar-remote'],
                ['checkout', '-b', 'my-cool-branch-name', '--track', 'foo_bar_baz-2845094/my-cool-branch-name'],
            )
            ->willReturn(
                // Report the branch doesn't exist.
                $this->throwException(new GitException(
                    "Command 'git rev-parse --verify --quiet sdadasd' failed (exit-code 1).",
                    1,
                    null,
                    new RunnerResult('git rev-parse --verify --quiet sdadasd', 1, [], []),
                )),
                $this->returnValue(['foo-remote', 'bar-remote']),
                $this->returnValue(['git@github.com:test-foo/remote.git']),
                $this->returnValue(['git@github.com:test-bar/remote.git']),
                [],
                [],
                ["Branch 'my-cool-branch-name' set up to track remote branch 'my-cool-branch-name' from 'foo_bar_baz-2845094/my-cool-branch-name'."]
            );

        $repo->expects($this->once())
            ->method('addRemote')
            ->with('foo_bar_baz-2845094', 'https://git.drupalcode.org/issue/foo_bar_baz-2845094.git');
        $repo->expects($this->once())->method('fetch')
            ->with('foo_bar_baz-2845094');

        $git = $this->getMockBuilder(Git::class)
            ->getMock();
        $git->expects($this->once())
            ->method('open')
            ->with($testRepoDir)
            ->willReturn($repo);

        $runner = $this->getMockBuilder(IRunner::class)
            ->getMock();

        $command = $this->getMockBuilder(ProjectMergeRequest::class)
            ->onlyMethods(['git'])
            ->disableOriginalConstructor()
            ->getMock();
        $command->expects($this->once())
            ->method('git')
            ->willReturn($git);
        $command->__construct($runner);

        $command->handlerStack()->push(new DogitGuzzleGitlabTestMiddleware());
        $tester = new CommandTester($command);
        $tester->setInputs([
            '8',
        ]);
        $result = $tester->execute([
            ProjectMergeRequestOptions::ARGUMENT_PROJECT => 'foo_bar_baz',
            ProjectMergeRequestOptions::ARGUMENT_DIRECTORY => $testRepoDir,
        ]);

        $this->assertEquals(0, $result);
        $this->assertStringContainsString('Found project foo_bar_baz with ID #13371337', $tester->getDisplay());
        $this->assertStringContainsString('Select merge request to checkout:', $tester->getDisplay());
        $this->assertStringContainsString('[9] Merge request !9: Issue #2866889: WSD on workflow module states page by dpi', $tester->getDisplay());
        $this->assertStringContainsString('[8] Merge request !8: Issue #2845094: Batch field creation by dpi', $tester->getDisplay());
        $this->assertStringContainsString('> 8', $tester->getDisplay());
        $this->assertStringContainsString('Checking out merge request !8: Issue #2845094: Batch field creation by dpi into directory /tmp/dogit-testing/fakedir', $tester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Directory `/tmp/dogit-testing/fakedir` looks like an existing Git repository.', $tester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Setting up new remote: foo_bar_baz-2845094 @ https://git.drupalcode.org/issue/foo_bar_baz-2845094.git', $tester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Fetching remote: foo_bar_baz-2845094 @ https://git.drupalcode.org/issue/foo_bar_baz-2845094.git', $tester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Checking out branch: my-cool-branch-name', $tester->getDisplay());
        $this->assertStringContainsString('[OK] Done', $tester->getDisplay());
    }

    /**
     * @covers ::execute
     */
    public function testNoMergeRequests(): void
    {
        $runner = $this->createMock(IRunner::class);
        $command = new ProjectMergeRequest($runner);
        $command->handlerStack()->push(new DogitGuzzleGitlabTestMiddleware());
        $tester = new CommandTester($command);
        $result = $tester->execute([
            ProjectMergeRequestOptions::ARGUMENT_PROJECT => 'nomrs',
            ProjectMergeRequestOptions::ARGUMENT_DIRECTORY => 'testdir',
        ]);
        $this->assertEquals(1, $result);

        $this->assertEquals(<<<OUTPUT

         Found project nomrs with ID #13371338

         [ERROR] No merge requests found.                                                                                       


        OUTPUT, $tester->getDisplay());
    }
}
