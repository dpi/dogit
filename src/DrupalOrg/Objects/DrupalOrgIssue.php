<?php

declare(strict_types=1);

namespace dogit\DrupalOrg\Objects;

use dogit\DrupalOrg\DrupalOrgObjectIterator;
use dogit\DrupalOrg\DrupalOrgObjectRepository;
use dogit\Utility;
use Psr\Http\Message\ResponseInterface;

class DrupalOrgIssue extends DrupalOrgObject
{
    protected int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function getCreated(): \DateTimeImmutable
    {
        if ($this->isStub) {
            throw new \DomainException('Data missing for stubs.');
        }

        if (!isset($this->data->created)) {
            throw new \DomainException('Missing created date');
        }

        $timestamp = $this->data->created;

        return new \DateTimeImmutable('@' . $timestamp);
    }

    public function getCurrentVersion(): string
    {
        if ($this->isStub) {
            throw new \DomainException('Data missing for stubs.');
        }

        if (!isset($this->data->field_issue_version)) {
            throw new \DomainException('Missing issue version');
        }

        return $this->data->field_issue_version;
    }

    /**
     * @return \dogit\DrupalOrg\Objects\DrupalOrgComment[]
     */
    public function commentsWithFiles(): array
    {
        $cids = array_unique(array_filter(array_map(
            fn (\stdClass $file) => (int) ($file->file->cid ?? null),
            $this->data->field_issue_files,
        )));

        return array_map(
            fn (int $cid) => $this->repository->share(DrupalOrgComment::fromStub((object) ['id' => $cid])),
            $cids
        );
    }

    public function getProjectName(): string
    {
        if (!isset($this->data->field_project->machine_name)) {
            throw new \DomainException('Data missing for stubs.');
        }

        return $this->data->field_project->machine_name;
    }

    public function getTitle(): string
    {
        if (!isset($this->data->title)) {
            throw new \DomainException('Data missing for stubs.');
        }

        return $this->data->title;
    }

    /**
     * @return \dogit\DrupalOrg\Objects\DrupalOrgComment[]
     *   Ordered chronologically
     */
    public function getComments(): array
    {
        return array_map(
            fn (\stdClass $comment) => $this->repository->share(DrupalOrgComment::fromStub($comment)),
            $this->data->comments ?? [],
        );
    }

    /**
     * @return int[]
     */
    public function getFiles(): array
    {
        return array_unique(array_filter(array_map(
            fn (\stdClass $file) => (int) $file->file->id ?? null,
            $this->data->field_issue_files,
        )));
    }

    /**
     * Patches ordered by comment and patch order within each comment.
     *
     * @return \Generator|\dogit\DrupalOrg\Objects\DrupalOrgPatch[]
     */
    public function getPatches(DrupalOrgObjectIterator $objectIterator): \Generator
    {
        $commentsWithPatches = Utility::filterCommentsWithPatches($objectIterator, $this->commentsWithFiles());
        // Transform file objects into patch objects.
        foreach ($commentsWithPatches as [$comment, $patchFiles]) {
            foreach ($patchFiles as $file) {
                yield DrupalOrgPatch::fromFile($file)->setParent($comment);
            }
        }
    }

    public static function fromStub(\stdClass $data): DrupalOrgIssue
    {
        if (!isset($data->id)) {
            throw new \InvalidArgumentException('ID is required');
        }

        $id = $data->id;
        $instance = new static((int) $id);
        unset($data->id);
        $instance->stubData = $data;
        $instance->isStub = true;

        return $instance;
    }

    public static function fromResponse(ResponseInterface $response, DrupalOrgObjectRepository $repository): DrupalOrgIssue
    {
        $data = json_decode((string) $response->getBody());
        $instance = new static((int) $data->nid);
        $instance
            ->setRepository($repository)
            ->importResponse($response);

        return $instance;
    }
}
