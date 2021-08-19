<?php

declare(strict_types=1);

namespace dogit\tests\Commands;

use CzProject\GitPhp\CommitId;
use CzProject\GitPhp\Git;
use CzProject\GitPhp\GitException;
use CzProject\GitPhp\GitRepository;
use CzProject\GitPhp\IRunner;
use dogit\Commands\Options\PatchToBranchOptions;
use dogit\Commands\PatchToBranch;
use dogit\ProcessFactory;
use dogit\tests\DogitGuzzleTestMiddleware;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * @coversDefaultClass \dogit\Commands\PatchToBranch
 */
final class PatchToBranchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        putenv('COLUMNS=120');
    }

    /**
     * @covers ::__construct
     * @covers ::configure
     * @covers ::execute
     * @covers ::getIssueEvents
     * @covers ::setupListeners
     * @covers ::createFinder
     */
    public function testCommand(): void
    {
        $testRepoDir = '/tmp/dogit-testing/fakedir';

        $repo = $this->createMock(GitRepository::class);
        // For \dogit\Listeners\PatchToBranch\ValidateLocalRepository\IsGit::__invoke
        $repo->expects($this->once())
            ->method('getBranches')
            ->willReturn(['abc', 'def']);
        $repo->expects($this->once())
            ->method('getRepositoryPath')
            ->willReturn($testRepoDir);
        $repo->expects($this->exactly(3))
            ->method('addAllChanges');

        $repo->expects($this->any())
            ->method('execute')
            ->withConsecutive(
                // For \dogit\Listeners\PatchToBranch\ValidateLocalRepository\IsClean::__invoke
                ['status', '--porcelain'],
                // For \dogit\Listeners\PatchToBranch\GitBranch\GitBranch::__invoke
                [['rev-parse', '--verify', '--quiet', 'dogit-2350939-8.2.x']],
                [['clean', '-f']],
                ['checkout', '-b', 'dogit-2350939-8.2.x', 'origin/8.2.x'],
                [['rev-list', '-1', '--before="1428704703"', 'remotes/origin/8.2.x']],
                [['rev-list', '-1', '--before="1428704703"', 'remotes/origin/8.3.x']],
                [['rev-list', '-1', '--before="1428704703"', 'remotes/origin/8.3.x']],
                ['reset', '--hard', '--quiet', 'abcdef0000000000000000000000000000000001'],
                [
                    'commit',
                    [
                        '--date',
                        '1428704703',
                    ],
                    '--author=larowlan <395439@larowlan.no-reply.drupal.org>',
                    '--allow-empty',
                    [
                        '--message',
                        <<<MESSAGE
                        Patch #1 on 8.2.x
                        
                        Patch URL: https://www.drupal.org/files/issues/alpha.patch
                        Comment URL: https://www.drupal.org/project/drupal/issues/2350939#comment-13370001
                        Issue URL: https://www.drupal.org/project/drupal/issues/2350939
                        Patch uploaded by larowlan
                        Commit built with dogit.dev
                        MESSAGE
                    ],
                ],
                [[
                    'show',
                    'cccccccccc000000000000000000000000000001',
                    '--name-only',
                    '--diff-filter=AR',
                    '--no-commit-id',
                ]],
                [
                    'commit',
                    '--amend',
                    '--allow-empty',
                    ['--reuse-message', 'dddddddddd000000000000000000000000000001'],
                    ['--date', 1428704703],
                    ['--author', 'dogit <dogit@dogit.dev>'],
                ],
                [['checkout', 'abcdef0000000000000000000000000000000002', '--', '.']],
                [
                    'commit',
                    [
                      '--date',
                      '1428704703',
                    ],
                    '--author=larowlan <395439@larowlan.no-reply.drupal.org>',
                    '--allow-empty',
                    [
                      '--message',
                      <<<MESSAGE
                            Patch #5 on 8.3.x
                            
                            Patch URL: https://www.drupal.org/files/issues/bravo.patch
                            Comment URL: https://www.drupal.org/project/drupal/issues/2350939#comment-13370005
                            Issue URL: https://www.drupal.org/project/drupal/issues/2350939
                            Patch uploaded by larowlan
                            Commit built with dogit.dev
                            MESSAGE
                  ],
                ],
                [[
                  'show',
                  'eeeeeeeeee000000000000000000000000000001',
                  '--name-only',
                  '--diff-filter=AR',
                  '--no-commit-id',
                ]],
                [['checkout', 'abcdef0000000000000000000000000000000003', '--', '.']],
                [
                    'commit',
                    [
                        '--date',
                        '1428704703',
                    ],
                    '--author=larowlan <395439@larowlan.no-reply.drupal.org>',
                    '--allow-empty',
                    [
                        '--message',
                        <<<MESSAGE
                            Patch #7 on 8.3.x
                            
                            Patch URL: https://www.drupal.org/files/issues/charlie.patch
                            Comment URL: https://www.drupal.org/project/drupal/issues/2350939#comment-13370005
                            Issue URL: https://www.drupal.org/project/drupal/issues/2350939
                            Patch uploaded by larowlan
                            Commit built with dogit.dev
                            MESSAGE
                    ],
                ],
                [[
                    'show',
                    'ffffffffff000000000000000000000000000001',
                    '--name-only',
                    '--diff-filter=AR',
                    '--no-commit-id',
                ]],
            )
            ->willReturn(
                // The working copy is clean:
                $this->returnValue([]),
                // The branch does not exist:
                $this->throwException(new GitException("Command 'git rev-parse --verify --quiet dogit-2350939-8.8.x' failed (exit-code 1).", 1, null)),
                $this->returnValue([]),
                $this->returnValue([]),
                $this->returnValue(['abcdef0000000000000000000000000000000001']),
                $this->returnValue(['abcdef0000000000000000000000000000000002']),
                $this->returnValue(['abcdef0000000000000000000000000000000003']),
                // reset
                $this->returnValue([]),
                // commit
                $this->returnValue([]),
                // show new files
                $this->returnValue(['new files 1.txt']),
                // commit amend
                $this->returnValue([]),
                // checkout pathspec
                $this->returnValue([]),
                $this->returnValue([]),
                // show new files
                $this->returnValue(['new files 2.txt']),
                $this->returnValue([]),
                $this->returnValue([]),
                // show new files 3
                $this->returnValue(['new files 3.txt']),
            );

        $repo->expects($this->exactly(6))
        ->method('getLastCommitId')
            ->willReturn(
                // First commit.
                $this->returnValue(new CommitId('cccccccccc000000000000000000000000000001')),
                // First merge.
                $this->returnValue(new CommitId('dddddddddd000000000000000000000000000001')),
                $this->returnValue(new CommitId('dddddddddd000000000000000000000000000001')),
                $this->returnValue(new CommitId('eeeeeeeeee000000000000000000000000000001')),
                $this->returnValue(new CommitId('eeeeeeeeee000000000000000000000000000001')),
                $this->returnValue(new CommitId('ffffffffff000000000000000000000000000001')),
            );

        $repo->expects($this->exactly(2))
            ->method('merge')
            ->withConsecutive(
                ['abcdef0000000000000000000000000000000002'],
                ['abcdef0000000000000000000000000000000003'],
            )
            ->willReturn(
                $this->returnSelf()
            );

        $git = $this->getMockBuilder(Git::class)
            ->getMock();
        $git->expects($this->once())
            ->method('open')
            ->with($testRepoDir)
            ->willReturn($repo);

        $runner = $this->getMockBuilder(IRunner::class)
            ->getMock();
        $finder = $this->createMock(Finder::class);
        $finder->expects($this->exactly(2))
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
        $finder->expects($this->exactly(2))
            ->method('in')
            ->with($testRepoDir)
            ->willReturnSelf();

        $process = $this->createMock(Process::class);
        $process->expects($this->exactly(3))
            ->method('wait')
            ->willReturn(Command::SUCCESS);
        $process->expects($this->exactly(3))
            ->method('getOutput')
            ->willReturn('Dummy output');
        $processFactory = $this->createMock(ProcessFactory::class);
        $processFactory->expects($this->exactly(6))
            ->method('createProcess')
            ->willReturn($process);

        $command = $this->getMockBuilder(PatchToBranch::class)
            ->onlyMethods(['git'])
            ->disableOriginalConstructor()
            ->getMock();
        $command->expects($this->once())
            ->method('git')
            ->willReturn($git);

        $command->__construct($runner, $finder, $processFactory);

        $command->handlerStack()->push(new DogitGuzzleTestMiddleware());
        $tester = new CommandTester($command);
        $result = $tester->execute(
            [
                PatchToBranchOptions::ARGUMENT_ISSUE_ID => '11110000',
                PatchToBranchOptions::ARGUMENT_WORKING_DIRECTORY => $testRepoDir,
            ],
            [
                'interactive' => false,
                'verbosity' => OutputInterface::VERBOSITY_DEBUG,
                'capture_stderr_separately' => true,
            ]
        );

        $this->assertEquals(0, $result);
        $output = $tester->getDisplay();
        $this->assertStringContainsString('Issue #2350939 for drupal at Sun, 05 Oct 2014 15:47:01 +0000: [PP-1] Implement a generic revision UI', $output);
        $this->assertStringContainsString('Batched comment requests for 13370001, 13370005, 13370007', $output);
        $this->assertStringContainsString('Batched file requests for 22220001, 22220002, 22220003, 22220004', $output);
        $this->assertStringContainsString('Batched comment requests for 13370002, 13370006, 13370008', $output);
        $this->assertStringContainsString('Checking patch versions by test result version.', $output);
        $this->assertStringContainsString('Comment #1: https://www.drupal.org/files/issues/alpha.patch guessed as 8.2.x (8.2.x)', $output);
        $this->assertStringContainsString('Comment #5: https://www.drupal.org/files/issues/bravo.patch guessed as 8.3.x (8.3.x)', $output);
        $this->assertStringContainsString("Comment #7: https://www.drupal.org/files/issues/charlie.patch guessed as 8.4.x (8.3.x) : May be reroll for older, detected as '8.4.x'", $output);
        $this->assertStringContainsString('Filtering patches', $output);
        $this->assertStringContainsString('Using directory /tmp/dogit-testing/fakedir for git repository.', $output);
        $this->assertStringContainsString('Checked out branch: dogit-2350939-8.2.x', $output);
        $this->assertStringContainsString('Applying patches to local repository', $output);
        $this->assertStringContainsString('Applying patch for comment #1 patch URL https://www.drupal.org/files/issues/alpha.patch', $output);
        $this->assertStringContainsString('Merging into commit #cccccccc before patch
[info] Applying patch for comment #5 patch URL https://www.drupal.org/files/issues/bravo.patch', $output);
        $this->assertStringContainsString('Merging into commit #eeeeeeee before patch', $output);
        $this->assertStringContainsString(' Applying patch for comment #7 patch URL https://www.drupal.org/files/issues/charlie.patch', $output);
        $this->assertStringContainsString('Object statistics', $output);
        $this->assertStringContainsString('Issues      1', $output);
        $this->assertStringContainsString('Comments    9', $output);
        $this->assertStringContainsString('Files       4', $output);
        $this->assertStringContainsString('Patches     3', $output);
        $this->assertStringContainsString('[OK] Done', $output);
        $this->assertStringContainsString("✨ Don't forget to like and subscribe — dogit.dev ✨", $output);
    }
}
