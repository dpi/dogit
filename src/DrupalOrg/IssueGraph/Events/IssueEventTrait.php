<?php

declare(strict_types=1);

namespace dogit\DrupalOrg\IssueGraph\Events;

use dogit\DrupalOrg\Objects\DrupalOrgComment;

trait IssueEventTrait
{
    protected DrupalOrgComment $comment;

    public function getComment(): DrupalOrgComment
    {
        return $this->comment;
    }
}
