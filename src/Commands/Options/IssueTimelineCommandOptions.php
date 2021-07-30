<?php

declare(strict_types=1);

namespace dogit\Commands\Options;

use Symfony\Component\Console\Input\InputInterface;

final class IssueTimelineCommandOptions
{
    public const ARGUMENT_ISSUE_ID = 'issue-id';
    public const OPTION_NO_COMMENTS = 'no-comments';
    public const OPTION_NO_EVENTS = 'no-events';

    public int $nid;
    public bool $noComments;
    public bool $noEvents;

    public static function fromInput(InputInterface $input): IssueTimelineCommandOptions
    {
        $instance = new static();

        /** @var string $nid */
        $nid = $input->getArgument(static::ARGUMENT_ISSUE_ID);
      if (preg_match('/^\d{1,10}$/m', $nid)) {
        $instance->nid = (int) $nid;
      }
      else {
        throw new \UnexpectedValueException('Issue ID is not valid');
      }
        $instance->noComments = (bool) $input->getOption(static::OPTION_NO_COMMENTS);
        $instance->noEvents = (bool) $input->getOption(static::OPTION_NO_EVENTS);

        return $instance;
    }
}
