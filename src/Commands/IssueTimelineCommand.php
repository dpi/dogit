<?php

declare(strict_types=1);

namespace dogit\Commands;

use dogit\Commands\Options\IssueTimelineCommandOptions;
use dogit\DrupalOrg\DrupalApi;
use dogit\DrupalOrg\DrupalOrgObjectIterator;
use dogit\DrupalOrg\DrupalOrgObjectRepository;
use dogit\DrupalOrg\IssueGraph\DrupalOrgIssueGraph;
use dogit\DrupalOrg\IssueGraph\Events\CommentEvent;
use dogit\DrupalOrg\IssueGraph\Events\IssueEventInterface;
use dogit\Utility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class IssueTimelineCommand extends Command
{
    use Traits\HttpTrait;

    protected static $defaultName = 'issue:timeline';

    protected function configure(): void
    {
        $this
            ->addArgument(IssueTimelineCommandOptions::ARGUMENT_ISSUE_ID, InputArgument::REQUIRED)
            ->addOption(IssueTimelineCommandOptions::OPTION_NO_COMMENTS, 'c', InputOption::VALUE_NONE)
            ->addOption(IssueTimelineCommandOptions::OPTION_NO_EVENTS, 'e', InputOption::VALUE_NONE)
            ->setAliases(['itl']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $logger = new ConsoleLogger($io);

        $options = IssueTimelineCommandOptions::fromInput($input);

        [$httpFactory, $httpAsyncClient] = $this->http($logger);

        $repository = new DrupalOrgObjectRepository();
        $api = new DrupalApi($httpFactory, $httpAsyncClient, $repository);
        $objectIterator = new DrupalOrgObjectIterator($api, $logger);
        $issue = $api->getIssue($options->nid);

        try {
            $events = iterator_to_array((new DrupalOrgIssueGraph(
                $httpFactory,
                $httpAsyncClient,
                $repository,
                $issue->url(),
            ))->graph());
        } catch (\Exception $e) {
            $io->error('Failed to build issue graph' . $e->getMessage());

            return self::FAILURE;
        }

        uasort($events, fn (IssueEventInterface $eventA, IssueEventInterface $eventB): int => $eventA->getComment()->id() <=> $eventB->getComment()->id());

        $events = array_filter(
            $events,
            fn (IssueEventInterface $event): bool => $event instanceof CommentEvent
                ? !$options->noComments
                : !$options->noEvents
        );

        // Group events by comment.
        $comments = [];
        $objectIterator->unstubComments(iterator_to_array(Utility::getCommentsFromEvents($events)));
        foreach ($events as $event) {
            $comments[$event->getComment()->id()][] = $event;
        }

        foreach ($comments as $events) {
            $firstComment = reset($events)->getComment();
            $io->title(sprintf('<href=%s>Comment #%s (%d) at %s</>',
                $firstComment->url(),
                $firstComment->id(),
                $firstComment->getSequence(),
                $firstComment->getCreated()->format('r'),
            ));
            foreach ($events as $event) {
                $io->text((string) $event);
            }
        }

        return Command::SUCCESS;
    }
}
