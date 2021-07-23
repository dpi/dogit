<?php

declare(strict_types=1);

namespace dogit\Commands;

use CzProject\GitPhp\Git;
use dogit\Commands\Options\IssueCloneOptions;
use dogit\DrupalOrg\DrupalApi;
use dogit\DrupalOrg\DrupalOrgObjectRepository;
use dogit\DrupalOrg\IssueGraph\DrupalOrgIssueGraph;
use dogit\DrupalOrg\IssueGraph\Events\IssueEvent;
use dogit\DrupalOrg\IssueGraph\Events\MergeRequestCreateEvent;
use dogit\Git\CliRunner;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Clones and checks out an existing Merge request for an issue.
 */
final class IssueClone extends Command
{
    use Traits\HttpTrait;

    protected static $defaultName = 'issue:clone';

    protected function configure(): void
    {
        $this
            ->setDescription('Interactively check out a MR for an issue.')
            ->addArgument(IssueCloneOptions::ARGUMENT_ISSUE_ID, InputArgument::REQUIRED)
            ->addArgument(IssueCloneOptions::ARGUMENT_DIRECTORY, InputArgument::REQUIRED)
            ->addOption(IssueCloneOptions::OPTION_COOKIE, null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Add cookies to HTTP requests.', [])
            ->addOption(IssueCloneOptions::OPTION_SINGLE, 's', InputOption::VALUE_NONE, '(no interaction) If there is only one merge request then check it out without prompting. If multiple merge requests are found then the command will quit.')
            ->addOption(IssueCloneOptions::OPTION_NO_CACHE, null, InputOption::VALUE_NONE, 'Whether to not use a HTTP cache.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $logger = new ConsoleLogger($io);
        $options = IssueCloneOptions::fromInput($input);
        $repository = new DrupalOrgObjectRepository();
        [$httpFactory, $httpAsyncClient] = $this->http($logger, $options->noHttpCache, $options->cookies);
        assert($httpFactory instanceof RequestFactoryInterface);
        assert($httpAsyncClient instanceof ClientInterface);
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

        $runner = new CliRunner();
        $git = new Git($runner);
        $git->cloneRepository(
            $mergeRequestCreateEvent->getGitUrl(),
            $options->directory,
            [
                '-b' => $mergeRequestCreateEvent->getGitBranch(),
            ],
        );

        $io->success('Done');

        return Command::SUCCESS;
    }
}
