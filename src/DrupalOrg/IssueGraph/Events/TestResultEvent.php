<?php

declare(strict_types=1);

namespace dogit\DrupalOrg\IssueGraph\Events;

use dogit\DrupalOrg\Objects\DrupalOrgComment;

final class TestResultEvent implements IssueEventInterface
{
    use IssueEventTrait;

    public function __construct(protected DrupalOrgComment $comment, protected string $version, protected string $result)
    {
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
