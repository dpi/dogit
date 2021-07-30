<?php

declare(strict_types=1);

namespace dogit\DrupalOrg\IssueGraph\Events;

use dogit\DrupalOrg\Objects\DrupalOrgComment;

final class StatusChangeEvent implements IssueEventInterface
{
    use IssueEventTrait;

    /**
     * For example 'Needs Work'.
     */
    protected string $from;

    /**
     * For example 'Needs review'.
     */
    protected string $to;

  protected DrupalOrgComment $comment;

  public function __construct(DrupalOrgComment $comment, string $from, string $to)
    {
      $this->comment = $comment;
      $this->from = trim($from, " \t\n\r\0\x0B»");
        $this->to = trim($to, " \t\n\r\0\x0B»");
    }

    public function from(): string
    {
        return $this->from;
    }

    public function to(): string
    {
        return $this->to;
    }

    public function __toString(): string
    {
        return sprintf('Status change from %s to %s', $this->from(), $this->to());
    }
}
