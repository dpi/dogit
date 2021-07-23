<?php

declare(strict_types=1);

namespace dogit\DrupalOrg\IssueGraph\Events;

use dogit\DrupalOrg\Objects\DrupalOrgComment;

interface IssueEventInterface
{
    public function getComment(): DrupalOrgComment;

    public function __toString(): string;
}
