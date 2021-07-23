<?php

declare(strict_types=1);

namespace dogit\DrupalOrg\IssueGraph\Events;

use dogit\DrupalOrg\Objects\DrupalOrgComment;

final class MergeRequestCreateEvent implements IssueEventInterface
{
    use IssueEventTrait;

    public function __construct(
        protected DrupalOrgComment $comment,
        protected string $mergeRequestUrl,
        protected string $project,
        protected int $mergeRequestId,
        protected string $repoUrlGit,
        protected string $repoUrlHttp,
        protected string $branch
    ) {
    }

    public function mergeRequestId(): int
    {
        return $this->mergeRequestId;
    }

    public function mergeRequestUrl(): string
    {
        return $this->mergeRequestUrl;
    }

    public function project(): string
    {
        return $this->project;
    }

    /**
     * The SSH URL.
     */
    public function getGitUrl(): string
    {
        return $this->repoUrlGit;
    }

    public function getGitHttpUrl(): string
    {
        return $this->repoUrlHttp;
    }

    public function getGitBranch(): string
    {
        return $this->branch;
    }

    public function getCloneCommand(): string
    {
        return sprintf('git clone -b %s %s', $this->getGitBranch(), $this->getGitUrl());
    }

    public function __toString(): string
    {
        return sprintf('Merge request !%d created: %s', $this->mergeRequestId(), $this->mergeRequestUrl());
    }
}
