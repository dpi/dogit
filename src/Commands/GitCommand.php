<?php

declare(strict_types=1);

namespace dogit\Commands;

use CzProject\GitPhp\Git;
use CzProject\GitPhp\IRunner;
use dogit\Commands\Options\GitCommandOptions;
use dogit\DrupalOrg\DrupalApi;
use dogit\DrupalOrg\DrupalOrgObjectIterator;
use dogit\DrupalOrg\DrupalOrgObjectRepository;
use dogit\DrupalOrg\IssueGraph\DrupalOrgIssueGraph;
use dogit\DrupalOrg\IssueGraph\Events\IssueEventInterface;
use dogit\DrupalOrg\Objects\DrupalOrgIssue;
use dogit\Events\GitCommand\FilterByResponseEvent;
use dogit\Events\GitCommand\FilterEvent;
use dogit\Events\GitCommand\GitApplyPatchesEvent;
use dogit\Events\GitCommand\GitBranchEvent;
use dogit\Events\GitCommand\TerminateEvent;
use dogit\Events\GitCommand\ValidateLocalRepositoryEvent;
use dogit\Events\GitCommand\VersionEvent;
use dogit\Git\CliRunner;
use dogit\Git\GitOperator;
use dogit\Listeners\GitCommand\Filter\ByConstraintOption;
use dogit\Listeners\GitCommand\Filter\ByExcludedCommentOption;
use dogit\Listeners\GitCommand\Filter\ByLastOption;
use dogit\Listeners\GitCommand\Filter\ByMetadata;
use dogit\Listeners\GitCommand\Filter\PrimaryTrunk;
use dogit\Listeners\GitCommand\FilterByResponse\ByBody;
use dogit\Listeners\GitCommand\GitApplyPatches\GitApplyPatches;
use dogit\Listeners\GitCommand\GitBranch\GitBranch;
use dogit\Listeners\GitCommand\Terminate\EndMessage;
use dogit\Listeners\GitCommand\Terminate\Statistics;
use dogit\Listeners\GitCommand\ValidateLocalRepository\IsClean;
use dogit\Listeners\GitCommand\ValidateLocalRepository\IsGit;
use dogit\Listeners\GitCommand\Version\ByTestResultsEvent;
use dogit\Listeners\GitCommand\Version\ByVersionChangeEvent;
use dogit\ProcessFactory;
use dogit\Utility;
use Http\Client\HttpAsyncClient;
use Psr\Http\Message\RequestFactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

/**
 * Converts patches in an issue to a Git branch.
 */
class GitCommand extends Command
{
    use Traits\HttpTrait;

    protected static $defaultName = 'git';
    protected Git $git;
    protected Finder $finder;
    protected ProcessFactory $processFactory;

    public function __construct(IRunner $runner = null, Finder $finder = null, ProcessFactory $processFactory = null)
    {
        parent::__construct();
        $this->git = $this->git($runner ?? new CliRunner());
        $this->finder = $finder ?? new Finder();
        $this->processFactory = $processFactory ?? new ProcessFactory();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(GitCommandOptions::ARGUMENT_ISSUE_ID, InputArgument::REQUIRED)
            ->addArgument(GitCommandOptions::ARGUMENT_WORKING_DIRECTORY, InputArgument::OPTIONAL, 'If omitted the current directory is used.')
            ->addArgument(GitCommandOptions::ARGUMENT_VERSION_CONSTRAINTS, InputArgument::OPTIONAL, 'Whether to limit patches by version.')
            ->addOption(GitCommandOptions::OPTION_BRANCH, 'b', InputOption::VALUE_OPTIONAL, 'Specify a custom branch name.')
            ->addOption(GitCommandOptions::OPTION_DELETE_EXISTING_BRANCH, 'd', InputOption::VALUE_NONE, 'Delete branch if it exists already.')
            ->addOption(GitCommandOptions::OPTION_EXCLUDE, 'e', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Excludes patches by comment sequence. The first comment is 1, second comment is 2, etc. A positive integer can be passed, or constraint-ish, such as "<=33". Valid prefix operators are <=, >=, <, >, !=, <>, or =.', [])
            ->addOption(GitCommandOptions::OPTION_COOKIE, null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Add cookies to HTTP requests.', [])
            ->addOption(GitCommandOptions::OPTION_PATCH_LEVEL, 'p', InputOption::VALUE_OPTIONAL, 'Number of leading components from file names to strip.', '1')
            ->addOption(GitCommandOptions::OPTION_NO_CACHE, null, InputOption::VALUE_NONE, 'Whether to not use a HTTP cache.')
            ->addOption(GitCommandOptions::OPTION_RESET, 'r', InputOption::VALUE_NONE, 'Whether to hard reset working directory if its not clean.')
            ->addOption(GitCommandOptions::OPTION_LAST, null, InputOption::VALUE_NONE, 'Whether to only include the last patch.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        assert($output instanceof ConsoleOutputInterface);
        $io = new SymfonyStyle($input, $output);
        $logger = new ConsoleLogger($io);

        try {
            $options = GitCommandOptions::fromInput($input);
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return static::FAILURE;
        }

        $repository = new DrupalOrgObjectRepository();
        [$httpFactory, $httpAsyncClient] = $this->http($logger, $options->noHttpCache, $options->cookies);
        $api = new DrupalApi($httpFactory, $httpAsyncClient, $repository);
        $objectIterator = new DrupalOrgObjectIterator($api, $logger);

        $issue = $api->getIssue($options->nid);
        $io->text(sprintf(
            '<href=%s>Issue #%s for %s at %s: %s</>',
            $issue->url(),
            $issue->id(),
            $issue->getProjectName(),
            $issue->getCreated()->format('r'),
            $issue->getTitle(),
        ));

        $dispatcher = new EventDispatcher();
        $this->setupListeners($dispatcher);

        $io->writeln('Building issue event graph');
        $issueEvents = $this->getIssueEvents($httpFactory, $httpAsyncClient, $repository, $issue);

        $io->writeln('Processing patches from comments');
        $patches = iterator_to_array($issue->getPatches($objectIterator), false);

        $io->writeln('Computing versions of patches');
        $event = new VersionEvent($patches, $issueEvents, $objectIterator, $logger);
        $dispatcher->dispatch($event, 'version');
        if ($event->isPropagationStopped()) {
            $io->error('Version computation failed.');

            return static::FAILURE;
        }

        $io->writeln('Filtering patches');
        $event = new FilterEvent($patches, $issueEvents, $logger, $options);
        $dispatcher->dispatch($event, 'filter');
        if ($event->isPropagationStopped()) {
            $io->error('Patch filtering failed.');

            return static::FAILURE;
        }

        $trunk = $event->getPatches();

        $io->writeln('Downloading patch files');
        try {
            $objectIterator->downloadPatchFiles($trunk);
        } catch (\Exception $e) {
            if ($e instanceof \Http\Client\Exception\HttpException) {
                $io->error(sprintf('Failed to download patch files: Got %s requesting %s', $e->getResponse()->getStatusCode(), (string) $e->getRequest()->getUri()));
            } else {
                $io->error(sprintf('Failed to download patch files: %s', $e->getMessage()));
            }

            return static::FAILURE;
        }

        $io->writeln('Filtering patches by response');
        $event = new FilterByResponseEvent($trunk, $logger);
        $dispatcher->dispatch($event, 'filter_by_response');

        $trunk = $event->getPatches();
        if (0 === count($trunk)) {
            $io->error('No suitable list of patches could be determined. Check parameters or run in verbose mode with -vvv.');

            return static::FAILURE;
        }

        // Git.
        try {
            $this->finder->directories()->in($options->gitDirectory);
        } catch (DirectoryNotFoundException) {
            $io->error(sprintf('Directory %s does not exist', $options->gitDirectory));

            return static::FAILURE;
        }
        $logger->debug('Using directory {working_directory} for git repository.', ['working_directory' => $options->gitDirectory]);

        $gitIo = GitOperator::fromDirectory($this->git, $options->gitDirectory);

        $io->writeln('Validating local repository.');
        $event = new ValidateLocalRepositoryEvent($gitIo, $options->gitDirectory, $logger, $options);
        $dispatcher->dispatch($event, 'git_validate_local');
        if ($event->isPropagationStopped()) {
            $io->error('Local repository is not valid.');

            return static::FAILURE;
        }

        $io->writeln('Creating local branch');
        $initialGitReference = reset($trunk)->getGitReference();
        $event = new GitBranchEvent($gitIo, $logger, $issue, $options, $initialGitReference);
        $dispatcher->dispatch($event, 'git_branch_create');
        if ($event->isPropagationStopped()) {
            $io->error('Failed to create local branch.');

            return static::FAILURE;
        }

        $io->writeln('Applying patches to local repository');
        $event = new GitApplyPatchesEvent($trunk, $gitIo, $input, $output, $issue, $options, false, $this->processFactory);
        $dispatcher->dispatch($event, 'git_apply_patches');
        if ($event->isPropagationStopped()) {
            $io->error('Failed to apply patches to local repository.');

            return static::FAILURE;
        }

        $event = new TerminateEvent($io, $logger, true, $repository);
        $dispatcher->dispatch($event, 'terminate');

        return Command::SUCCESS;
    }

    protected function setupListeners(EventDispatcher $dispatcher): void
    {
        $dispatcher->addListener('version', new ByVersionChangeEvent());
        $dispatcher->addListener('version', new ByTestResultsEvent());
        $dispatcher->addListener('filter', new ByMetadata());
        $dispatcher->addListener('filter', new PrimaryTrunk());
        $dispatcher->addListener('filter', new ByConstraintOption());
        $dispatcher->addListener('filter', new ByExcludedCommentOption());
        $dispatcher->addListener('filter', new ByLastOption());
        $dispatcher->addListener('filter_by_response', new ByBody());
        $dispatcher->addListener('git_validate_local', new IsGit());
        $dispatcher->addListener('git_validate_local', new IsClean());
        $dispatcher->addListener('git_branch_create', new GitBranch());
        $dispatcher->addListener('git_apply_patches', new GitApplyPatches());
        $dispatcher->addListener('terminate', new Statistics());
        $dispatcher->addListener('terminate', new EndMessage());
    }

    /**
     * @return \dogit\DrupalOrg\IssueGraph\Events\IssueEventInterface[]
     *   Events ordered by time
     */
    private function getIssueEvents(RequestFactoryInterface $httpFactory, HttpAsyncClient $httpAsyncClient, DrupalOrgObjectRepository $repository, DrupalOrgIssue $issue): array
    {
        $issueEvents = iterator_to_array((new DrupalOrgIssueGraph(
            $httpFactory,
            $httpAsyncClient,
            $repository,
            $issue->url(),
        ))->graph());

        // Order events by time. Using getCreated would be more reliable, but is
        // very expensive, requiring external requests.
        uasort($issueEvents, fn (IssueEventInterface $eventA, IssueEventInterface $eventB): int => $eventA->getComment()->id() <=> $eventB->getComment()->id());

        // Ensure there is a version change for the first comment so versionAt
        // can compute version for patches before the first explicit version
        // change.
        return Utility::ensureInitialVersionChange($issueEvents, $issue);
    }

    protected function git(IRunner $runner): Git
    {
        return new Git($runner);
    }
}
