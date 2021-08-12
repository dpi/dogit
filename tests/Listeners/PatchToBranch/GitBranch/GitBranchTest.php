<?php

declare(strict_types=1);

namespace dogit\tests\Listeners\PatchToBranch\GitBranch;

use CzProject\GitPhp\GitException;
use CzProject\GitPhp\GitRepository;
use dogit\Commands\Options\PatchToBranchOptions;
use dogit\Commands\PatchToBranch;
use dogit\DrupalOrg\Objects\DrupalOrgIssue;
use dogit\Events\PatchToBranch\GitBranchEvent;
use dogit\Git\GitOperator;
use dogit\Listeners\PatchToBranch\GitBranch\GitBranch;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * @coversDefaultClass \dogit\Listeners\PatchToBranch\GitBranch\GitBranch
 */
final class GitBranchTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @covers ::__invoke
     */
    public function testListenerBranchUnspecified(): void
    {
        $gitRepository = $this->createMock(GitRepository::class);
        $gitRepository->expects($this->any())
            ->method('execute')
            ->withConsecutive(
                [['rev-parse', '--verify', '--quiet', 'dogit-1337-2.1.x']],
                [['clean', '-f']],
                ['checkout', '-b', 'dogit-1337-2.1.x', 'origin/2.1.x'],
            )
            ->willReturn(
                // The branch does not exist:
                $this->throwException(new GitException("Command 'git rev-parse --verify --quiet dogit-1337-2.1.x' failed (exit-code 1).", 1, null)),
                [],
                [],
            );

        $gitOperator = new GitOperator($gitRepository);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Starting branch at 2.1.x'],
                ['Checked out branch: dogit-1337-2.1.x'],
            );

        $issue = $this->createMock(DrupalOrgIssue::class);
        $issue->method('id')->willReturn(1337);

        $command = new PatchToBranch();
        $input = new ArrayInput([
            PatchToBranchOptions::ARGUMENT_ISSUE_ID => '11110003',
            PatchToBranchOptions::ARGUMENT_WORKING_DIRECTORY => '/tmp/dir',
            // Intentionally empty:
            '--' . PatchToBranchOptions::OPTION_BRANCH => '',
        ], $command->getDefinition());
        $options = PatchToBranchOptions::fromInput($input);
        $initialGitReference = '2.1.x';

        $event = new GitBranchEvent($gitOperator, $logger, $issue, $options, $initialGitReference);
        $filter = new GitBranch();

        $filter($event);
        $this->assertFalse($event->isPropagationStopped());
    }

    /**
     * @covers ::__invoke
     */
    public function testListenerBranchNotExistsNoDelete(): void
    {
        $gitRepository = $this->createMock(GitRepository::class);
        $gitRepository->expects($this->any())
            ->method('execute')
            ->withConsecutive(
                [['rev-parse', '--verify', '--quiet', 'dogit-1337-2.1.x']],
                [['clean', '-f']],
                ['checkout', '-b', 'dogit-1337-2.1.x', 'origin/2.1.x'],
            )
            ->willReturn(
                // The branch does not exist:
                $this->throwException(new GitException("Command 'git rev-parse --verify --quiet dogit-1337-2.1.x' failed (exit-code 1).", 1, null)),
                [],
                [],
            );

        $gitOperator = new GitOperator($gitRepository);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Starting branch at 2.1.x'],
                ['Checked out branch: dogit-1337-2.1.x'],
            );

        $issue = $this->createMock(DrupalOrgIssue::class);
        $issue->method('id')->willReturn(1337);

        $command = new PatchToBranch();
        $input = new ArrayInput([
            PatchToBranchOptions::ARGUMENT_ISSUE_ID => '11110003',
            PatchToBranchOptions::ARGUMENT_WORKING_DIRECTORY => '/tmp/dir',
            '--' . PatchToBranchOptions::OPTION_BRANCH => '',
        ], $command->getDefinition());
        $options = PatchToBranchOptions::fromInput($input);
        $initialGitReference = '2.1.x';

        $event = new GitBranchEvent($gitOperator, $logger, $issue, $options, $initialGitReference);
        $filter = new GitBranch();

        $filter($event);
        $this->assertFalse($event->isPropagationStopped());
    }

    /**
     * @covers ::__invoke
     */
    public function testListenerBranchExistsNoDelete(): void
    {
        $gitRepository = $this->createMock(GitRepository::class);
        $gitRepository->expects($this->any())
            ->method('execute')
            ->withConsecutive(
                [['rev-parse', '--verify', '--quiet', 'dogit-1337-2.1.x']],
            )
            ->willReturn(
                [],
            );

        $gitOperator = new GitOperator($gitRepository);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('Git branch dogit-1337-2.1.x already exists from a previous run. Specify a unique branch name with --branch or use --delete-existing-branch.');
        $issue = $this->createMock(DrupalOrgIssue::class);
        $issue->method('id')->willReturn(1337);

        $command = new PatchToBranch();
        $input = new ArrayInput([
            PatchToBranchOptions::ARGUMENT_ISSUE_ID => '11110003',
            PatchToBranchOptions::ARGUMENT_WORKING_DIRECTORY => '/tmp/dir',
            '--' . PatchToBranchOptions::OPTION_BRANCH => '',
        ], $command->getDefinition());
        $options = PatchToBranchOptions::fromInput($input);
        $initialGitReference = '2.1.x';

        $event = new GitBranchEvent($gitOperator, $logger, $issue, $options, $initialGitReference);
        $filter = new GitBranch();

        $filter($event);
        $this->assertTrue($event->isPropagationStopped());
    }

    /**
     * @covers ::__invoke
     */
    public function testListenerBranchExistsWithDelete(): void
    {
        $gitRepository = $this->createMock(GitRepository::class);
        $gitRepository->expects($this->exactly(5))
            ->method('execute')
            ->withConsecutive(
                [['rev-parse', '--verify', '--quiet', 'dogit-1337-2.1.x']],
                [
                    'branch',
                    '-M',
                    'dogit-1337-2.1.x',
                    'dogit-1337-2.1.x-to-delete',
                ],
                [['clean', '-f']],
                ['checkout', '-b', 'dogit-1337-2.1.x', 'origin/2.1.x'],
                ['branch', '-D', 'dogit-1337-2.1.x-to-delete']
            )
            ->willReturn(
                [],
            );

        $gitOperator = new GitOperator($gitRepository);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with('Renaming existing branch {branch_name} so it can be deleted after a new branch with the same name is created.', [
                'branch_name' => 'dogit-1337-2.1.x',
            ]);
        $logger->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['Starting branch at 2.1.x'],
                ['Checked out branch: dogit-1337-2.1.x'],
                ['Deleting old branch {branch_name}', [
                    'branch_name' => 'dogit-1337-2.1.x-to-delete',
                ]],
            );
        $issue = $this->createMock(DrupalOrgIssue::class);
        $issue->method('id')->willReturn(1337);

        $command = new PatchToBranch();
        $input = new ArrayInput([
            PatchToBranchOptions::ARGUMENT_ISSUE_ID => '11110003',
            PatchToBranchOptions::ARGUMENT_WORKING_DIRECTORY => '/tmp/dir',
            '--' . PatchToBranchOptions::OPTION_BRANCH => '',
            // Delete existing branch:
            '--' . PatchToBranchOptions::OPTION_DELETE_EXISTING_BRANCH => true,
        ], $command->getDefinition());
        $options = PatchToBranchOptions::fromInput($input);
        $initialGitReference = '2.1.x';

        $event = new GitBranchEvent($gitOperator, $logger, $issue, $options, $initialGitReference);
        $filter = new GitBranch();
        $filter->branchToDeleteSuffixGenerator = fn (string $branchName) => $branchName . '-to-delete';

        $filter($event);
        $this->assertFalse($event->isPropagationStopped());
    }
}
