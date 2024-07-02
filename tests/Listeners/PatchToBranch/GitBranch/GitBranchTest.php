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
use dogit\tests\DogitTestBase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * @coversDefaultClass \dogit\Listeners\PatchToBranch\GitBranch\GitBranch
 */
final class GitBranchTest extends DogitTestBase
{
    use ProphecyTrait;

    /**
     * @covers ::__invoke
     */
    public function testListenerBranchUnspecified(): void
    {
        $gitRepository = \Mockery::mock(GitRepository::class);
        $gitRepository->expects('execute')
            ->with(['rev-parse', '--verify', '--quiet', 'dogit-1337-2.1.x'])
            // The branch does not exist:
            ->andThrows(new GitException("Command 'git rev-parse --verify --quiet dogit-1337-2.1.x' failed (exit-code 1).", 1, null));

        $gitRepository->expects('execute')
            ->with(['clean', '-f'])
            ->andReturn([]);
        $gitRepository->expects('execute')
            ->with('checkout', '-b', 'dogit-1337-2.1.x', 'origin/2.1.x')
            ->andReturn([]);

        $gitOperator = new GitOperator($gitRepository);
        $logger = \Mockery::mock(LoggerInterface::class);
        $logger->expects('info')->with('Starting branch at 2.1.x');
        $logger->expects('info')->with('Checked out branch: dogit-1337-2.1.x');

        $issue = \Mockery::mock(DrupalOrgIssue::class);
        $issue->expects('id')->andReturn(1337);

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
        $gitRepository = \Mockery::mock(GitRepository::class);
        // The branch does not exist:
        $gitRepository->expects('execute')->with(['rev-parse', '--verify', '--quiet', 'dogit-1337-2.1.x'])->andThrow(new GitException("Command 'git rev-parse --verify --quiet dogit-1337-2.1.x' failed (exit-code 1).", 1, null));
        $gitRepository->expects('execute')->with(['clean', '-f'])->andReturn([]);
        $gitRepository->expects('execute')->with('checkout', '-b', 'dogit-1337-2.1.x', 'origin/2.1.x')->andReturn([]);

        $gitOperator = new GitOperator($gitRepository);
        $logger = \Mockery::mock(LoggerInterface::class);
        $logger->expects('info')->with('Starting branch at 2.1.x');
        $logger->expects('info')->with('Checked out branch: dogit-1337-2.1.x');

        $issue = \Mockery::mock(DrupalOrgIssue::class);
        $issue->expects('id')->andReturn(1337);

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
        $gitRepository = \Mockery::mock(GitRepository::class);
        $gitRepository->expects('execute')->with(['rev-parse', '--verify', '--quiet', 'dogit-1337-2.1.x'])->andReturn([]);

        $gitOperator = new GitOperator($gitRepository);
        $logger = \Mockery::mock(LoggerInterface::class);
        $logger->expects('error')
            ->with('Git branch dogit-1337-2.1.x already exists from a previous run. Specify a unique branch name with --branch or use --delete-existing-branch.');
        $issue = \Mockery::mock(DrupalOrgIssue::class);
        $issue->expects('id')->andReturn(1337);

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
        $gitRepository = \Mockery::mock(GitRepository::class);
        $gitRepository->expects('execute')->with(['rev-parse', '--verify', '--quiet', 'dogit-1337-2.1.x'])->andReturn([]);
        $gitRepository->expects('execute')->with(
            'branch',
            '-M',
            'dogit-1337-2.1.x',
            'dogit-1337-2.1.x-to-delete',
        )->andReturn([]);
        $gitRepository->expects('execute')->with(['clean', '-f'])->andReturn([]);
        $gitRepository->expects('execute')->with('checkout', '-b', 'dogit-1337-2.1.x', 'origin/2.1.x')->andReturn([]);
        $gitRepository->expects('execute')->with('branch', '-D', 'dogit-1337-2.1.x-to-delete')->andReturn([]);

        $gitOperator = new GitOperator($gitRepository);
        $logger = \Mockery::mock(LoggerInterface::class);
        $logger->expects('debug')->with('Renaming existing branch {branch_name} so it can be deleted after a new branch with the same name is created.', [
            'branch_name' => 'dogit-1337-2.1.x',
        ]);
        $logger->expects('info')->with('Starting branch at 2.1.x');
        $logger->expects('info')->with('Checked out branch: dogit-1337-2.1.x');
        $logger->expects('info')->with('Deleting old branch {branch_name}', ['branch_name' => 'dogit-1337-2.1.x-to-delete']);

        $issue = \Mockery::mock(DrupalOrgIssue::class);
        $issue->expects('id')->andReturn(1337);

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
