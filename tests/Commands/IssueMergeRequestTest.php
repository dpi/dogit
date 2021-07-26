<?php

declare(strict_types=1);

namespace dogit\tests\Commands;

use CzProject\GitPhp\Git;
use CzProject\GitPhp\GitException;
use CzProject\GitPhp\GitRepository;
use CzProject\GitPhp\IRunner;
use CzProject\GitPhp\RunnerResult;
use dogit\Commands\IssueMergeRequest;
use dogit\Commands\Options\IssueMergeRequestOptions;
use dogit\tests\DogitGuzzleTestMiddleware;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @coversDefaultClass \dogit\Commands\IssueMergeRequest
 */
final class IssueMergeRequestTest extends TestCase
{
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

        $command = $this->getMockBuilder(IssueMergeRequest::class)
            ->onlyMethods(['git'])
            ->disableOriginalConstructor()
            ->getMock();
        $command->expects($this->once())
            ->method('git')
            ->willReturn($git);
        $command->__construct($runner);

        $command->handlerStack()->push(new DogitGuzzleTestMiddleware());
        $tester = new CommandTester($command);
        $result = $tester->execute([
            IssueMergeRequestOptions::ARGUMENT_ISSUE_ID => '11110003',
            IssueMergeRequestOptions::ARGUMENT_DIRECTORY => $testRepoDir,
        ]);
        $this->assertEquals(0, $result);

        $this->assertEquals(<<<OUTPUT
        
         Issue #11110003 for drupal at Sun, 05 Oct 2014 15:47:01 +0000: Issue for testing with merge requests
        
         Please select merge request to checkout [Merge request !333: my-cool-branch-name from comment #9]:
          [333] Merge request !333: my-cool-branch-name from comment #9
         > 
         ! [NOTE] Checking out merge request !333: my-cool-branch-name from comment #9 into directory /tmp/dogit-testing/fakedir
        
         ! [NOTE] Interpreting directory `/tmp/dogit-testing/fakedir` as not a Git repository, cloning...                       
        
         [OK] Done                                                                                                              
        

        OUTPUT, $tester->getDisplay());
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
                ['checkout', '-b', 'my-cool-branch-name', '--track', 'drupal-11110003/my-cool-branch-name'],
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
                ["Branch 'dasds' set up to track remote branch '3073549-increase-visibility-of' from 'scheduled_transitions-3073549'."]
            );

        $repo->expects($this->once())
            ->method('addRemote')
            ->with('drupal-11110003', 'git@git.drupal.org:issue/drupal-11110003.git');
        $repo->expects($this->once())->method('fetch')
            ->with('drupal-11110003');

        $git = $this->getMockBuilder(Git::class)
            ->getMock();
        $git->expects($this->once())
            ->method('open')
            ->with($testRepoDir)
            ->willReturn($repo);

        $runner = $this->getMockBuilder(IRunner::class)
            ->getMock();

        $command = $this->getMockBuilder(IssueMergeRequest::class)
            ->onlyMethods(['git'])
            ->disableOriginalConstructor()
            ->getMock();
        $command->expects($this->once())
            ->method('git')
            ->willReturn($git);
        $command->__construct($runner);

        $command->handlerStack()->push(new DogitGuzzleTestMiddleware());
        $tester = new CommandTester($command);
        $tester->setInputs([]);
        $result = $tester->execute([
            IssueMergeRequestOptions::ARGUMENT_ISSUE_ID => '11110003',
            IssueMergeRequestOptions::ARGUMENT_DIRECTORY => $testRepoDir,
        ]);
        $this->assertEquals(0, $result);

        $this->assertEquals(<<<OUTPUT
        
         Issue #11110003 for drupal at Sun, 05 Oct 2014 15:47:01 +0000: Issue for testing with merge requests
        
         Please select merge request to checkout [Merge request !333: my-cool-branch-name from comment #9]:
          [333] Merge request !333: my-cool-branch-name from comment #9
         > 
         ! [NOTE] Checking out merge request !333: my-cool-branch-name from comment #9 into directory /tmp/dogit-testing/fakedir
        
         ! [NOTE] Directory `/tmp/dogit-testing/fakedir` looks like an existing Git repository.                                 
        
         ! [NOTE] No existing HTTP remote for this merge request found.                                                         
        
         ! [NOTE] Setting up new remote: drupal-11110003 @ git@git.drupal.org:issue/drupal-11110003.git                         
        
         ! [NOTE] Fetching remote: drupal-11110003 @ git@git.drupal.org:issue/drupal-11110003.git                               
        
         ! [NOTE] Checking out branch: my-cool-branch-name                                                                      
        
         [OK] Done                                                                                                              
        
        
        OUTPUT, $tester->getDisplay());
    }

    /**
     * @covers ::execute
     */
    public function testNoMergeRequests(): void
    {
        $runner = $this->createMock(IRunner::class);
        $command = new IssueMergeRequest($runner);
        $command->handlerStack()->push(new DogitGuzzleTestMiddleware());
        $tester = new CommandTester($command);
        $result = $tester->execute(['issue-id' => '11110002', 'testdir']);
        $this->assertEquals(1, $result);

        $this->assertEquals(<<<OUTPUT
        
         Issue #11110002 for drupal at Sun, 05 Oct 2014 15:47:01 +0000: Issue with no merge requests
        
         [ERROR] No merge requests found.                                                                                       
        
        
        OUTPUT, $tester->getDisplay());
    }
}
