<?php

declare(strict_types=1);

namespace dogit\DrupalOrg\IssueGraph\Events;

use dogit\DrupalOrg\Objects\DrupalOrgComment;

final class MergeRequestCreateEvent implements IssueEventInterface
{
    use IssueEventTrait;

    protected DrupalOrgComment $comment;

    protected string $branch;

    protected string $repoUrlHttp;

    protected string $repoUrlGit;

    protected int $mergeRequestId;

    protected string $project;

    protected string $mergeRequestUrl;

    public function __construct(
        DrupalOrgComment $comment,
        string $mergeRequestUrl,
        string $project,
        int $mergeRequestId,
        string $repoUrlGit,
        string $repoUrlHttp,
        string $branch
    ) {
        $this->mergeRequestUrl = $mergeRequestUrl;
        $this->project = $project;
        $this->mergeRequestId = $mergeRequestId;
        $this->repoUrlGit = $repoUrlGit;
        $this->repoUrlHttp = $repoUrlHttp;
        $this->branch = $branch;
        $this->comment = $comment;
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
