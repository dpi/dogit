<?php

declare(strict_types=1);

namespace dogit\Commands;

use CzProject\GitPhp\Git;
use CzProject\GitPhp\GitException;
use CzProject\GitPhp\IRunner;
use dogit\Commands\Options\IssueMergeRequestOptions;
use dogit\DrupalOrg\DrupalApi;
use dogit\DrupalOrg\DrupalOrgObjectRepository;
use dogit\DrupalOrg\IssueGraph\DrupalOrgIssueGraph;
use dogit\DrupalOrg\IssueGraph\Events\IssueEvent;
use dogit\DrupalOrg\IssueGraph\Events\MergeRequestCreateEvent;
use dogit\Git\CliRunner;
use dogit\Git\GitOperator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

/**
 * Clones or checks out an existing Merge request for an issue.
 */
class IssueMergeRequest extends Command
{
    use Traits\HttpTrait;

    protected static $defaultName = 'issue:mr';
    protected Git $git;
    protected Finder $finder;

    public function __construct(IRunner $runner = null, ?Finder $finder = null)
    {
        parent::__construct();
        $this->git = $this->git($runner ?? new CliRunner());
        $this->finder = $finder ?? new Finder();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Interactively check out a MR for an issue.')
            ->addArgument(IssueMergeRequestOptions::ARGUMENT_ISSUE_ID, InputArgument::REQUIRED)
            ->addArgument(IssueMergeRequestOptions::ARGUMENT_DIRECTORY, InputArgument::OPTIONAL, 'The repository directory.', '.')
            ->addOption(IssueMergeRequestOptions::OPTION_COOKIE, null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Add cookies to HTTP requests.', [])
            ->addOption(IssueMergeRequestOptions::OPTION_HTTP, null, InputOption::VALUE_NONE, 'Use HTTP instead of SSH.')
            ->addOption(IssueMergeRequestOptions::OPTION_SINGLE, 's', InputOption::VALUE_NONE, '(no interaction) If there is only one merge request then check it out without prompting. If multiple merge requests are found then the command will quit.')
            ->addOption(IssueMergeRequestOptions::OPTION_NO_CACHE, null, InputOption::VALUE_NONE, 'Whether to not use a HTTP cache.')
            ->setAliases(['imr']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $logger = new ConsoleLogger($io);
        $options = IssueMergeRequestOptions::fromInput($input);
        $repository = new DrupalOrgObjectRepository();
        [$httpFactory, $httpAsyncClient] = $this->http($logger, $options->noHttpCache, $options->cookies);
        $api = new DrupalApi($httpFactory, $httpAsyncClient, $repository);

        $issue = $api->getIssue($options->nid);
        $io->text(sprintf(
            '<href=%s>Issue #%s for %s at %s: %s</>',
            $issue->url(),
            $issue->id(),
            $issue->getProjectName(),
            $issue->getCreated()->format('r'),
            $issue->getTitle(),
        ));

        /** @var \dogit\DrupalOrg\IssueGraph\Events\IssueEventInterface[] $events */
        $events = iterator_to_array((new DrupalOrgIssueGraph(
            $httpFactory,
            $httpAsyncClient,
            $repository,
            $issue->url(),
        ))->graph(), false);

        $mergeRequestCreateEvents = IssueEvent::filterMergeRequestCreateEvents($events);
        if (0 === count($mergeRequestCreateEvents)) {
            $io->error('No merge requests found.');

            return Command::FAILURE;
        }

        // Remap to MR IDs.
        /** @var \dogit\DrupalOrg\IssueGraph\Events\MergeRequestCreateEvent[] $mergeRequestCreateEvents */
        $mergeRequestCreateEvents = array_combine(
            array_map(
                fn (MergeRequestCreateEvent $mergeRequestCreateEvent): int => $mergeRequestCreateEvent->mergeRequestId(),
                $mergeRequestCreateEvents,
            ),
            $mergeRequestCreateEvents,
        );

        $choices = array_map(
            fn (MergeRequestCreateEvent $mergeRequestCreateEvent): string => sprintf('Merge request !%d: %s from comment #%d', $mergeRequestCreateEvent->mergeRequestId(), $mergeRequestCreateEvent->getGitBranch(), $mergeRequestCreateEvent->getComment()->getSequence()),
            $mergeRequestCreateEvents,
        );

        if ($options->single) {
            if (1 === count($mergeRequestCreateEvents)) {
                $io->note('Found a single MR.');
                $mergeRequestCreateEvent = reset($mergeRequestCreateEvents);
            } else {
                $io->error('Multiple merge requests found. Re-run this command without --single.');

                return Command::FAILURE;
            }
        } else {
            $response = $io->choice(
                'Please select merge request to checkout',
                $choices,
                1 === count($choices) ? array_key_first($choices) : 0,
            );
            $mrId = array_search($response, $choices, true);
            $mergeRequestCreateEvent = $mergeRequestCreateEvents[$mrId];
        }

        $io->note(sprintf(
            'Checking out merge request !%d: %s from comment #%d into directory %s',
            $mergeRequestCreateEvent->mergeRequestId(),
            $mergeRequestCreateEvent->getGitBranch(),
            $mergeRequestCreateEvent->getComment()->getSequence(),
            $options->directory,
        ));

        // If this is an existing repo.
        try {
            $gitIo = GitOperator::fromDirectory($this->git, $options->directory, $this->createFinder());
            $io->note('Directory `' . $options->directory . '` looks like an existing Git repository.');
        } catch (GitException) {
            $io->note('Interpreting directory `' . $options->directory . '` as not a Git repository, cloning...');

            $this->git->cloneRepository(
                $mergeRequestCreateEvent->getGitUrl(),
                $options->directory,
                [
                    '-b' => $mergeRequestCreateEvent->getGitBranch(),
                ],
            );

            $io->success('Done');

            return Command::SUCCESS;
        } catch (DirectoryNotFoundException) {
            $io->error('Directory `' . $options->directory . '` does not exist.');

            return Command::SUCCESS;
        }

        // Check branch name.
        $branch = $mergeRequestCreateEvent->getGitBranch();
        while ($gitIo->branchExists($branch)) {
            $branch = $io->ask(sprintf('Local branch with name `%s` already exists. Enter new branch name:', $branch), $branch);
        }

        $remotes = $gitIo->getRemotes();
        $remoteUrls = [];
        foreach ($remotes as $remoteName) {
            $urls = array_fill_keys(
                $gitIo->getRemoteUrls($remoteName),
                $remoteName,
            );
            $remoteUrls = array_merge($remoteUrls, $urls);
        }

        // Check if the SSH or HTTP URL for the MR we want to check out is already a remote:
        if ($options->isHttp && isset($remoteUrls[$mergeRequestCreateEvent->getGitHttpUrl()])) {
            $gitUrl = $mergeRequestCreateEvent->getGitHttpUrl();
            $remoteName = $remoteUrls[$gitUrl];
            $io->note(sprintf('Found existing HTTP remote for this merge request: %s @ %s', $remoteName, $gitUrl));
        } elseif (!$options->isHttp && isset($remoteUrls[$mergeRequestCreateEvent->getGitUrl()])) {
            $gitUrl = $mergeRequestCreateEvent->getGitUrl();
            $remoteName = $remoteUrls[$gitUrl];
            $io->note(sprintf('Found existing SSH remote for this merge request: %s @ %s', $remoteName, $gitUrl));
        } else {
            $gitUrl = $options->isHttp ? $mergeRequestCreateEvent->getGitHttpUrl() : $mergeRequestCreateEvent->getGitUrl();
            $io->note('No existing remote for this merge request found.');

            // Generate a unique remote name.
            $remoteName = null;
            $suffix = '';
            while (null === $remoteName || in_array($remoteName, $remotes, true)) {
                $remoteName = $mergeRequestCreateEvent->project() . '-' . $issue->id() . $suffix;
                // Create a suffix for next run if the first attempt doesn't succeed:
                $suffix = '-' . time();
            }

            $io->note(sprintf('Setting up new remote: %s @ %s', $remoteName, $gitUrl));
            $gitIo->addRemote($remoteName, $gitUrl);
        }

        $io->note(sprintf('Fetching remote: %s @ %s', $remoteName, $gitUrl));
        $gitIo->fetchRemote($remoteName);

        $io->note(sprintf('Checking out branch: %s', $branch));
        $gitIo->checkoutNewTrackCustom($branch, $remoteName, $mergeRequestCreateEvent->getGitBranch());

        $io->success('Done');

        return Command::SUCCESS;
    }

    protected function git(IRunner $runner): Git
    {
        return new Git($runner);
    }

    /**
     * A small factory that creates finders.
     */
    private function createFinder(): Finder
    {
        return clone $this->finder;
    }
}
