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
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

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
     * @covers ::__construct
     */
    public function testCommandClone(): void
    {
        $testRepoDir = '/tmp/dogit-testing/fakedir';

        $git = $this->getMockBuilder(Git::class)
            ->getMock();
        $git->expects($this->once())
            ->method('cloneRepository')
            // Expect SSH endpoint:
            ->with('git@git.drupal.org:issue/foo_bar_baz-2845094.git', '/tmp/dogit-testing/fakedir');

        $runner = $this->getMockBuilder(IRunner::class)
            ->getMock();

        $command = $this->getMockBuilder(ProjectMergeRequest::class)
            ->onlyMethods(['git'])
            ->disableOriginalConstructor()
            ->getMock();
        $command->expects($this->once())
            ->method('git')
            ->willReturn($git);

        $finder = $this->createMock(Finder::class);
        $finder->expects($this->once())
            ->method('directories')
            ->willReturnSelf();
        $finder->expects($this->once())
            ->method('ignoreVCS')
            ->willReturnSelf();
        $finder->expects($this->once())
            ->method('depth')
            ->willReturnSelf();
        $finder->expects($this->once())
            ->method('ignoreDotFiles')
            ->willReturnSelf();
        $finder->expects($this->once())
            ->method('name')
            ->willReturnSelf();
        $finder->expects($this->once())
            ->method('count')
            // No Git repo.
            ->willReturn(0);
        $finder->expects($this->once())
            ->method('in')
            ->with($testRepoDir)
            ->willReturnSelf();
        $command->__construct($runner, $finder);

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
        $this->assertStringContainsString('Checking out merge request !8: Issue #2845094: Batch field creation by dpi into directory /tmp/dogit-testing/fakedir', $tester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Interpreting directory `/tmp/dogit-testing/fakedir` as not a Git repository, cloning..', $tester->getDisplay());
        $this->assertStringContainsString('[OK] Done', $tester->getDisplay());
    }

    /**
     * @covers ::execute
     */
    public function testCommandCloneHttp(): void
    {
        $testRepoDir = '/tmp/dogit-testing/fakedir';

        $git = $this->getMockBuilder(Git::class)
            ->getMock();
        $git->expects($this->once())
            ->method('open')
            ->with($testRepoDir)
            ->willThrowException(new GitException("Repository 'Blurgh' not found."));
        $git->expects($this->once())
            ->method('cloneRepository')
            // Expect HTTP endpoint:
            ->with('https://git.drupalcode.org/issue/foo_bar_baz-2845094.git', '/tmp/dogit-testing/fakedir');

        $runner = $this->getMockBuilder(IRunner::class)
            ->getMock();

        $command = $this->getMockBuilder(ProjectMergeRequest::class)
            ->onlyMethods(['git'])
            ->disableOriginalConstructor()
            ->getMock();
        $command->expects($this->once())
            ->method('git')
            ->willReturn($git);

        $finder = $this->createMock(Finder::class);
        $finder->expects($this->once())
            ->method('directories')
            ->willReturnSelf();
        $finder->expects($this->once())
            ->method('ignoreVCS')
            ->willReturnSelf();
        $finder->expects($this->once())
            ->method('depth')
            ->willReturnSelf();
        $finder->expects($this->once())
            ->method('ignoreDotFiles')
            ->willReturnSelf();
        $finder->expects($this->once())
            ->method('name')
            ->willReturnSelf();
        $finder->expects($this->once())
            ->method('count')
            ->willReturn(1);
        $finder->expects($this->once())
            ->method('in')
            ->with($testRepoDir)
            ->willReturnSelf();
        $command->__construct($runner, $finder);

        $command->handlerStack()->push(new DogitGuzzleGitlabTestMiddleware());
        $tester = new CommandTester($command);
        $tester->setInputs([
            '8',
        ]);
        $result = $tester->execute([
            ProjectMergeRequestOptions::ARGUMENT_PROJECT => 'foo_bar_baz',
            ProjectMergeRequestOptions::ARGUMENT_DIRECTORY => $testRepoDir,
            '--' . ProjectMergeRequestOptions::OPTION_HTTP => true,
        ]);

        $this->assertEquals(0, $result);
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
            ->with('foo_bar_baz-2845094', 'git@git.drupal.org:issue/foo_bar_baz-2845094.git');
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

        $finder = $this->createMock(Finder::class);
        $finder->expects($this->once())
            ->method('directories')
            ->willReturnSelf();
        $finder->expects($this->once())
            ->method('ignoreVCS')
            ->willReturnSelf();
        $finder->expects($this->once())
            ->method('depth')
            ->willReturnSelf();
        $finder->expects($this->once())
            ->method('ignoreDotFiles')
            ->willReturnSelf();
        $finder->expects($this->once())
            ->method('name')
            ->willReturnSelf();
        $finder->expects($this->once())
            ->method('count')
            ->willReturn(1);
        $finder->expects($this->once())
            ->method('in')
            ->with($testRepoDir)
            ->willReturnSelf();
        $command->__construct($runner, $finder);

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
        $this->assertStringContainsString('Checking out merge request !8: Issue #2845094: Batch field creation by dpi into directory /tmp/dogit-testing/fakedir', $tester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Directory `/tmp/dogit-testing/fakedir` looks like an existing Git repository.', $tester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Setting up new remote: foo_bar_baz-2845094 @ git@git.drupal.org:issue/foo_bar_baz-2845094.git', $tester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Fetching remote: foo_bar_baz-2845094 @ git@git.drupal.org:issue/foo_bar_baz-2845094.git', $tester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Checking out branch: my-cool-branch-name', $tester->getDisplay());
        $this->assertStringContainsString('[OK] Done', $tester->getDisplay());
    }

    public function testDirectoryNotExists(): void
    {
        $testRepoDir = '/tmp/dogit-testing/fakedir';

        $git = $this->getMockBuilder(Git::class)
            ->getMock();

        $runner = $this->getMockBuilder(IRunner::class)
            ->getMock();

        $command = $this->getMockBuilder(ProjectMergeRequest::class)
            ->onlyMethods(['git'])
            ->disableOriginalConstructor()
            ->getMock();
        $command->expects($this->once())
            ->method('git')
            ->willReturn($git);

        $finder = $this->createMock(Finder::class);
        $finder->expects($this->once())
            ->method('directories')
            ->willReturnSelf();
        $finder->expects($this->once())
            ->method('in')
            ->with($testRepoDir)
            ->willThrowException(new DirectoryNotFoundException());
        $command->__construct($runner, $finder);

        $command->handlerStack()->push(new DogitGuzzleGitlabTestMiddleware());
        $tester = new CommandTester($command);
        $tester->setInputs([
            '8',
        ]);
        $result = $tester->execute([
            ProjectMergeRequestOptions::ARGUMENT_PROJECT => 'foo_bar_baz',
            ProjectMergeRequestOptions::ARGUMENT_DIRECTORY => $testRepoDir,
        ]);

        $this->assertEquals(1, $result);
        $this->assertStringContainsString('[ERROR] Directory `/tmp/dogit-testing/fakedir` does not exist.', $tester->getDisplay());
    }

    /**
     * @covers ::execute
     */
    public function testProjectNotFound(): void
    {
        $runner = $this->createMock(IRunner::class);
        $command = new ProjectMergeRequest($runner);
        $command->handlerStack()->push(new DogitGuzzleGitlabTestMiddleware());
        $tester = new CommandTester($command);
        $result = $tester->execute([
            ProjectMergeRequestOptions::ARGUMENT_PROJECT => 'project-doesnt-exist',
            ProjectMergeRequestOptions::ARGUMENT_DIRECTORY => 'testdir',
        ]);
        $this->assertEquals(1, $result);

        $this->assertEquals(<<<OUTPUT

         [ERROR] Project not found: project-doesnt-exist                                                                        \n

        OUTPUT, $tester->getDisplay());
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

    /**
     * @covers ::execute
     */
    public function testProjectFromComposerJson(): void
    {
        $runner = $this->createMock(IRunner::class);

        $command = $this->getMockBuilder(ProjectMergeRequest::class)
            ->onlyMethods(['git'])
            ->disableOriginalConstructor()
            ->getMock();

        $command->__construct($runner);

        $command->handlerStack()->push(new DogitGuzzleGitlabTestMiddleware());
        $tester = new CommandTester($command);
        $tester->setInputs([
            '8',
        ]);
        chdir(__DIR__ . '/../fixtures/composerFiles/valid/');
        $result = $tester->execute([]);
        $this->assertEquals(0, $result);
        $this->assertStringContainsString('Detected project name foo_bar_baz from composer.json file.', $tester->getDisplay());
    }

    /**
     * @covers ::execute
     */
    public function testProjectFromComposerJsonError(): void
    {
        $runner = $this->createMock(IRunner::class);

        $command = $this->getMockBuilder(ProjectMergeRequest::class)
            ->onlyMethods(['git'])
            ->disableOriginalConstructor()
            ->getMock();

        $command->__construct($runner);

        $command->handlerStack()->push(new DogitGuzzleGitlabTestMiddleware());
        $tester = new CommandTester($command);
        $tester->setInputs([
            '8',
        ]);
        chdir(__DIR__ . '/../fixtures/composerFiles/malformed/');
        $result = $tester->execute([]);
        $this->assertEquals(1, $result);
        $this->assertStringContainsString('[ERROR] Failed to parse composer.json: Syntax error', $tester->getDisplay());
    }

    /**
     * @covers \dogit\Commands\Options\ProjectMergeRequestOptions
     */
    public function testOptions(): void
    {
        $runner = $this->createMock(IRunner::class);
        $command = new ProjectMergeRequest($runner);

        $input = new ArrayInput([
            ProjectMergeRequestOptions::ARGUMENT_DIRECTORY => '/tmp/blah',
            ProjectMergeRequestOptions::ARGUMENT_PROJECT => 'a_project',
            '--' . ProjectMergeRequestOptions::OPTION_ALL => true,
            '--' . ProjectMergeRequestOptions::OPTION_BRANCH => 'the-branch',
            '--' . ProjectMergeRequestOptions::OPTION_HTTP => true,
            '--' . ProjectMergeRequestOptions::OPTION_ONLY_CLOSED => true,
            '--' . ProjectMergeRequestOptions::OPTION_ONLY_MERGED => true,
            '--' . ProjectMergeRequestOptions::OPTION_NO_CACHE => true,
        ], $command->getDefinition());
        $options = ProjectMergeRequestOptions::fromInput($input);

        $this->assertEquals('/tmp/blah', $options->directory);
        $this->assertEquals('a_project', $options->project);
        $this->assertTrue($options->includeAll);
        $this->assertEquals('the-branch', $options->branchName);
        $this->assertTrue($options->isHttp);
        $this->assertTrue($options->onlyMerged);
        $this->assertTrue($options->onlyClosed);
        $this->assertTrue($options->noHttpCache);
    }
}
