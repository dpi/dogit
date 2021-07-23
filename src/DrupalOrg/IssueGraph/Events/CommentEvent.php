<?php

declare(strict_types=1);

namespace dogit\DrupalOrg\IssueGraph\Events;

use dogit\DrupalOrg\Objects\DrupalOrgComment;

final class CommentEvent implements IssueEventInterface
{
    use IssueEventTrait;

    public function __construct(protected DrupalOrgComment $comment)
    {
    }

    public function __toString(): string
    {
        return sprintf(
            '🗣 Comment by %s %s on %s',
            $this->comment->isBot() ? '🤖' : '👤',
            $this->comment->getAuthorName(),
            $this->comment->getCreated()->format('r'),
        );
    }
}
