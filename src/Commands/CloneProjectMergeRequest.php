<?php

declare(strict_types=1);

namespace dogit\Commands;

use CzProject\GitPhp\Git;
use dogit\Commands\Options\CloneProjectMergeRequestOptions as Options;
use dogit\Git\CliRunner;
use Gitlab\Api\MergeRequests;
use Gitlab\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Clones a merge request for a project.
 *
 * User is presented with merge requests for a project and may choose
 * which merge request to clone. By default, only open merge requests are
 * listed.
 */
final class CloneProjectMergeRequest extends Command
{
    use Traits\HttpTrait;

    protected static $defaultName = 'project:clone:mr';

    protected function configure(): void
    {
        $this
            ->setDescription('Interactively check out a MR for a project.')
            ->addArgument(Options::ARGUMENT_PROJECT, InputArgument::REQUIRED)
            ->addArgument(Options::ARGUMENT_DIRECTORY, InputArgument::REQUIRED)
            ->addOption(Options::OPTION_ALL, 'a', InputOption::VALUE_NONE, 'Whether to show merge requests regardless of state.')
            ->addOption(Options::OPTION_HTTP, null, InputOption::VALUE_NONE, 'Use HTTP instead of SSH.')
            ->addOption(Options::OPTION_ONLY_CLOSED, 'c', InputOption::VALUE_NONE, 'Whether to show only closed merge requests.')
            ->addOption(Options::OPTION_ONLY_MERGED, 'm', InputOption::VALUE_NONE, 'Whether to show only merged merge requests.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $logger = new ConsoleLogger($io);
        $options = Options::fromInput($input);

        $state = match (true) {
            $options->onlyClosed => MergeRequests::STATE_CLOSED,
            $options->onlyMerged => MergeRequests::STATE_MERGED,
            $options->includeAll => MergeRequests::STATE_ALL,
            default => MergeRequests::STATE_OPENED,
        };

        $client = new Client();
        $client->setUrl('https://git.drupalcode.org');

        //path, name,
        $project = $client->projects()->show(sprintf('project/%s', $options->project));
        $projectId = $project['id'] ?? null;
        if (!$projectId) {
            $io->error('Invalid project or identifier.');

            return static::FAILURE;
        }

        $io->text(sprintf(
            'Found project <href=%s>%s</> with ID #%s',
            $project['web_url'],
            $project['name'],
            $projectId,
        ));

        $mergeRequests = $client->mergeRequests()->all($projectId, [
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
            'Please select merge request to checkout',
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
        $sourceProject = $client->projects()->show($sourceProjectId);

        $repoUrl = $options->isHttp
            ? $sourceProject['ssh_url_to_repo']
            : $sourceProject['http_url_to_repo'];

        $runner = new CliRunner();
        $git = new Git($runner);
        $git->cloneRepository(
            $repoUrl,
            $options->directory,
            [
                '-b' => $mergeRequest['source_branch'],
            ],
        );

        $io->success('Done');

        return Command::SUCCESS;
    }
}
