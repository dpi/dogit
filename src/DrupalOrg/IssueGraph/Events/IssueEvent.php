<?php

declare(strict_types=1);

namespace dogit\DrupalOrg\IssueGraph\Events;

use dogit\DrupalOrg\Objects\DrupalOrgComment;

final class IssueEvent implements IssueEventInterface
{
    use IssueEventTrait;

  protected DrupalOrgComment $comment;

  protected array $data;

  /**
     * @param mixed[] $data
     */
    public function __construct(DrupalOrgComment $comment, array $data)
    {
      $this->data = $data;
      $this->comment = $comment;
    }

    /**
     * @param mixed[] $data
     */
    public static function fromRaw(DrupalOrgComment $comment, string $type, array $data): IssueEventInterface
    {
        return match ($type) {
            'Status' => new StatusChangeEvent($comment, ...$data),
            'Version' => new VersionChangeEvent($comment, ...$data),
            'Assigned' => new AssignmentChangeEvent($comment, ...$data),
            default => new static($comment, $data),
        };
    }

    /**
     * @param \dogit\DrupalOrg\IssueGraph\Events\IssueEventInterface[] $events
     *
     * @return \dogit\DrupalOrg\IssueGraph\Events\MergeRequestCreateEvent[]
     */
    public static function filterMergeRequestCreateEvents(array $events): array
    {
        return array_filter(
            $events,
            fn (IssueEventInterface $event): bool => $event instanceof MergeRequestCreateEvent
        );
    }

    /**
     * @param \dogit\DrupalOrg\IssueGraph\Events\IssueEventInterface[] $events
     *
     * @return \dogit\DrupalOrg\IssueGraph\Events\VersionChangeEvent[]
     */
    public static function filterVersionChangeEvents(array $events): array
    {
        return array_filter($events, fn (IssueEventInterface $event): bool => $event instanceof VersionChangeEvent);
    }

    /**
     * @return mixed[]
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function __toString(): string
    {
        return 'Generic event';
    }
}
