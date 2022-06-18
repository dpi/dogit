<?php

declare(strict_types=1);

namespace dogit\DrupalOrg\Objects;

use dogit\DrupalOrg\DrupalOrgObjectRepository;
use Psr\Http\Message\ResponseInterface;

class DrupalOrgComment extends DrupalOrgObject
{
    protected int $sequence;

    /**
     * @var \dogit\DrupalOrg\Objects\DrupalOrgFile[]
     */
    protected array $files;

    public function __construct(protected int $id)
    {
    }

    public function getCreated(): \DateTimeImmutable
    {
        if ($this->isStub) {
            throw new \DomainException('Data missing for stubs.');
        }
        $timestamp = $this->data->created ?? throw new \DomainException('Missing created date');

        return new \DateTimeImmutable('@' . $timestamp);
    }

    /**
     * @return \dogit\DrupalOrg\Objects\DrupalOrgFile[]
     */
    public function getFiles(): array
    {
        $this->files ?? throw new \DomainException('Data missing for stubs.');

        return $this->files;
    }

    /**
     * @param \dogit\DrupalOrg\Objects\DrupalOrgFile[] $files
     */
    public function setFiles(array $files): static
    {
        // Rekey by ID so result of \dogit\Utility::getFilesFromComments is
        // usable with iterator_to_array without $preserve_keys = FALSE.
        $this->files = array_combine(array_map(fn (DrupalOrgFile $file): int => $file->id(), $files), $files);

        return $this;
    }

    public function isBot(): bool
    {
        return 'System Message' === $this->getAuthorName();
    }

    public function getAuthorName(): string
    {
        return $this->data->name ?? throw new \DomainException('Data missing for stubs.');
    }

    public function getAuthorId(): int
    {
        return (int) ($this->data->author->id ?? throw new \DomainException('Data missing for stubs.'));
    }

    public function getIssue(): DrupalOrgIssue
    {
        if ($this->isStub) {
            throw new \DomainException('Data missing for stubs.');
        }

        return $this->repository->share(DrupalOrgIssue::fromStub($this->data->node));
    }

    public function getSequence(): int
    {
        return $this->sequence;
    }

    public function setSequence(int $sequence): static
    {
        $this->sequence = $sequence;

        return $this;
    }

    public function getComment(): string
    {
        $commentBody = $this->data->comment_body ?? throw new \DomainException('Data missing for stubs.');

        return isset($commentBody->value) && strlen($commentBody->value) > 0 ? $commentBody->value : '';
    }

    public function importResponse(ResponseInterface $response): static
    {
        parent::importResponse($response);
        $this->setFiles(array_map(
            fn (\stdClass $file): DrupalOrgFile => $this->repository->share(DrupalOrgFile::fromStub($file)),
            $this->data->comment_files ?? [],
        ));

        return $this;
    }

    public static function fromStub(\stdClass $data): static
    {
        $data->id ?? throw new \InvalidArgumentException('ID is required');
        // References from issues to comments are 'id' not 'cid'.
        $instance = new static((int) $data->id);
        $instance->stubData = $data;
        $instance->isStub = true;

        return $instance;
    }

    public static function fromResponse(ResponseInterface $response, DrupalOrgObjectRepository $repository): static
    {
        $data = json_decode((string) $response->getBody());
        // ID's in responses are 'cid', not 'id' per fromStub().
        $instance = new static((int) $data->cid);
        $instance
            ->setRepository($repository)
            ->importResponse($response);

        return $instance;
    }
}
