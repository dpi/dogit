<?php

declare(strict_types=1);

namespace dogit\DrupalOrg\IssueGraph\Events;

use dogit\DrupalOrg\Objects\DrupalOrgComment;

final class VersionChangeEvent implements IssueEventInterface
{
    use IssueEventTrait;

  protected string $to;

  protected string $from;

  protected DrupalOrgComment $comment;

  public function __construct(DrupalOrgComment $comment, string $from, string $to)
    {
      $this->comment = $comment;
      $this->from = $from;
      $this->to = $to;
      foreach ([&$this->from, &$this->to] as &$version) {
            $version = trim($version, " \t\n\r\0\x0B»");
            if (str_ends_with($version, '-dev')) {
                $version = substr($version, 0, -4);
            }
        }
    }

    /**
     * @return string
     *   For example '9.1.x'.
     */
    public function from(): string
    {
        return $this->from;
    }

    /**
     * @return string
     *   For example '9.2.x'.
     */
    public function to(): string
    {
        return $this->to;
    }

    public function __toString(): string
    {
        return sprintf('Version changed from %s to %s', $this->from(), $this->to());
    }
}
