<?php

declare(strict_types=1);

namespace dogit\DrupalOrg\Objects;

use dogit\DrupalOrg\DrupalOrgObjectRepository;
use Psr\Http\Message\ResponseInterface;

abstract class DrupalOrgObject
{
    protected int $id;
    protected bool $isStub = true;
    protected \stdClass $stubData;
    protected \stdClass $data;
    protected DrupalOrgObjectRepository $repository;

    abstract public function __construct(int $id);

    public function id(): int
    {
        return $this->id;
    }

    public function url(): string
    {
        if ($this->isStub) {
            throw new \DomainException('Data missing for stubs.');
        }

        return $this->data->url;
    }

    public function isStub(): bool
    {
        return $this->isStub;
    }

    /**
     * Imports data into this instance.
     */
    public function importResponse(ResponseInterface $response): void
    {
        $this->data = \json_decode((string) $response->getBody());
        $this->isStub = false;
    }

    /**
     * @return $this
     */
    public function setRepository(DrupalOrgObjectRepository $repository): DrupalOrgObject
    {
        $this->repository = $repository;

        return $this;
    }

    abstract public static function fromStub(\stdClass $data): DrupalOrgObject;

    /**
     * Creates a new object.
     */
    abstract public static function fromResponse(ResponseInterface $response, DrupalOrgObjectRepository $repository): DrupalOrgObject;
}
