<?php

declare(strict_types=1);

namespace dogit\Commands;

use CzProject\GitPhp\Git;
use CzProject\GitPhp\GitException;
use CzProject\GitPhp\IRunner;
use dogit\Commands\Options\ProjectMergeRequestOptions as Options;
use dogit\Git\CliRunner;
use dogit\Git\GitOperator;
use dogit\Utility;
use Gitlab\Api\MergeRequests;
use Gitlab\Client as GitlabClient;
use Gitlab\HttpClient\Builder;
use Http\Client\Exception\HttpException;
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
 * Clones a merge request for a project.
 *
 * User is presented with merge requests for a project and may choose
 * which merge request to clone. By default, only open merge requests are
 * listed.
 */
class ProjectMergeRequest extends Command
{
    use Traits\HttpTrait;

    protected static $defaultName = 'project:mr';
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
            ->setDescription('Interactively check out a MR for a project.')
            ->addArgument(Options::ARGUMENT_PROJECT, InputArgument::OPTIONAL, 'The project name.')
            ->addArgument(Options::ARGUMENT_DIRECTORY, InputArgument::OPTIONAL, 'The repository directory.', '.')
            ->addOption(Options::OPTION_ALL, 'a', InputOption::VALUE_NONE, 'Whether to show merge requests regardless of state.')
            ->addOption(Options::OPTION_BRANCH, 'b', InputOption::VALUE_OPTIONAL, 'Specify a custom branch name.')
            ->addOption(Options::OPTION_HTTP, null, InputOption::VALUE_NONE, 'Use HTTP instead of SSH.')
            ->addOption(Options::OPTION_NO_CACHE, null, InputOption::VALUE_NONE, 'Whether to not use a HTTP cache.')
            ->addOption(Options::OPTION_ONLY_CLOSED, 'c', InputOption::VALUE_NONE, 'Whether to show only closed merge requests.')
            ->addOption(Options::OPTION_ONLY_MERGED, 'm', InputOption::VALUE_NONE, 'Whether to show only merged merge requests.')
            ->setAliases(['pmr']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $logger = new ConsoleLogger($io);
        $options = Options::fromInput($input);

        // When project argument is not provided, try to detect the project name from a composer.json.
        if (null === $options->project) {
            try {
                $options->project = Utility::drupalProjectNameFromComposerJson($options->directory, $this->createFinder());
                $io->text(sprintf('Detected project name %s from composer.json file.', $options->project));
            } catch (\InvalidArgumentException $e) {
                $io->error($e->getMessage());

                return static::FAILURE;
            }
        }

        $state = match (true) {
            $options->onlyClosed => MergeRequests::STATE_CLOSED,
            $options->onlyMerged => MergeRequests::STATE_MERGED,
            $options->includeAll => MergeRequests::STATE_ALL,
            default => MergeRequests::STATE_OPENED,
        };

        [$httpFactory, $httpAsyncClient] = $this->http($logger, $options->noHttpCache, []);
        $httpClientBuilder = new Builder($httpAsyncClient, $httpFactory);
        $gitlab = new GitlabClient($httpClientBuilder);
        $gitlab->setUrl('https://git.drupalcode.org');

        try {
            $project = $gitlab->projects()->show(sprintf('project/%s', $options->project));
        } catch (HttpException $e) {
            $io->error(404 === $e->getCode()
                ? sprintf('Project not found: %s', $options->project)
                : sprintf('Error getting project: %s', $options->project)
            );

            return static::FAILURE;
        }

        if (null === ($projectId = $project['id'] ?? null)) {
            $io->error('Project identifier missing.');

            return static::FAILURE;
        }

        $io->text(sprintf(
            'Found project <href=%s>%s</> with ID #%s',
            $project['web_url'],
            $project['name'],
            $projectId,
        ));

        $mergeRequests = $gitlab->mergeRequests()->all($projectId, [
            'state' => $state,
        ]);
        if (0 === count($mergeRequests)) {
            $io->error('No merge requests found.');

            return Command::FAILURE;
        }

        // Remap to MR IDs.
        $mergeRequests = array_combine(
            array_map(
                // Use the per project MR ID not sitewide ID.
                fn (array $mergeRequests): int => (int) $mergeRequests['iid'],
                $mergeRequests,
            ),
            $mergeRequests,
        );

        $mrLabel = function (array $mergeRequest): string {
            return sprintf(
                '%s: <href=%s>%s</> by %s',
                $mergeRequest['references']['short'],
                $mergeRequest['web_url'],
                $mergeRequest['title'],
                $mergeRequest['author']['name'],
            );
        };

        $choices = array_map(
            fn (array $mergeRequest): string => sprintf('Merge request %s', $mrLabel($mergeRequest)),
            $mergeRequests,
        );

        $response = $io->choice(
            'Select merge request to checkout',
            $choices,
        );
        $mrId = array_search($response, $choices, true);
        $mergeRequest = $mergeRequests[$mrId];

        $io->text(sprintf(
            'Checking out merge request %s into directory %s',
            $mrLabel($mergeRequest),
            $options->directory,
        ));

        $sourceProjectId = (int) $mergeRequest['source_project_id'];
        $sourceProject = $gitlab->projects()->show($sourceProjectId);

        $gitUrl = $options->isHttp
            ? $sourceProject['http_url_to_repo']
            : $sourceProject['ssh_url_to_repo'];

        $remoteBranchName = $mergeRequest['source_branch'];
        $localBranchName = $options->branchName ?? $remoteBranchName;

        // If this is an existing repo.
        try {
            $gitIo = GitOperator::fromDirectory($this->git, $options->directory, $this->createFinder());
            $io->note('Directory `' . $options->directory . '` looks like an existing Git repository.');
        } catch (GitException) {
            $io->note('Interpreting directory `' . $options->directory . '` as not a Git repository, cloning...');

            $this->git->cloneRepository(
                $gitUrl,
                $options->directory,
                [
                    '-b' => $remoteBranchName,
                ],
            );

            $io->success('Done');

            return Command::SUCCESS;
        } catch (DirectoryNotFoundException) {
            $io->error('Directory `' . $options->directory . '` does not exist.');

            return Command::FAILURE;
        }

        // Check branch name.
        while ($gitIo->branchExists($localBranchName)) {
            $localBranchName = $io->ask(sprintf('Local branch with name `%s` already exists. Enter new branch name:', $localBranchName), $localBranchName);
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

        // Check if the remote URL for the MR we want to check out is already a remote:
        if (isset($remoteUrls[$gitUrl])) {
            $remoteName = $remoteUrls[$gitUrl];
            $io->note(sprintf('Found existing remote for this merge request: %s @ %s', $remoteName, $gitUrl));
        } else {
            $io->note('No existing remote for this merge request found.');

            // Generate a unique remote name.
            $remoteName = null;
            $suffix = '';
            while (null === $remoteName || in_array($remoteName, $remotes, true)) {
                $remoteName = $sourceProject['name'] . $suffix;
                // Create a suffix for next run if the first attempt doesn't succeed:
                $suffix = '-' . time();
            }

            $io->note(sprintf('Setting up new remote: %s @ %s', $remoteName, $gitUrl));
            $gitIo->addRemote($remoteName, $gitUrl);
        }

        $io->note(sprintf('Fetching remote: %s @ %s', $remoteName, $gitUrl));
        $gitIo->fetchRemote($remoteName);

        $io->note(sprintf('Checking out branch: %s', $localBranchName));
        $gitIo->checkoutNewTrackCustom($localBranchName, $remoteName, $remoteBranchName);

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
