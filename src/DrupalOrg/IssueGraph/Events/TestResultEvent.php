<?php

declare(strict_types=1);

namespace dogit\DrupalOrg\IssueGraph\Events;

use dogit\DrupalOrg\Objects\DrupalOrgComment;

final class TestResultEvent implements IssueEventInterface
{
    use IssueEventTrait;

  protected string $result;

  protected string $version;

  protected DrupalOrgComment $comment;

  public function __construct(DrupalOrgComment $comment, string $version, string $result)
    {
      $this->comment = $comment;
      $this->version = $version;
      $this->result = $result;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function result(): string
    {
        return $this->result;
    }

    public function __toString(): string
    {
        $result = $this->result();
        $result = sprintf('%s %s', str_contains($result, 'fail') ? '❌' : '✅', $result);

        return sprintf(
            '🧪 Test result: %s: %s',
            $this->version(),
            $result,
        );
    }
}
