<?php

declare(strict_types=1);

namespace dogit\DrupalOrg;

use dogit\DrupalOrg\Objects\DrupalOrgComment;
use dogit\DrupalOrg\Objects\DrupalOrgFile;
use dogit\DrupalOrg\Objects\DrupalOrgIssue;
use dogit\DrupalOrg\Objects\DrupalOrgPatch;
use Http\Promise\Promise;

/**
 * Synchronous methods automatically use object repository.
 */
interface DrupalApiInterface
{
    public function getIssue(int $nid): DrupalOrgIssue;

    public function getCommentAsync(DrupalOrgComment $comment): Promise;

    public function getFileAsync(DrupalOrgFile $file): Promise;

    public function getPatchFileAsync(DrupalOrgPatch $patch): Promise;
}
