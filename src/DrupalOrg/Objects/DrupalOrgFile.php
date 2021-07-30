<?php

declare(strict_types=1);

namespace dogit\DrupalOrg\Objects;

use dogit\DrupalOrg\DrupalOrgObjectRepository;
use Psr\Http\Message\ResponseInterface;

class DrupalOrgFile extends DrupalOrgObject
{
    protected int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public ?DrupalOrgObject $parent = null;

    public function getMime(): string
    {
        if ($this->isStub) {
            throw new \DomainException('Data missing for stubs.');
        }
        if (!isset($this->data->mime)) {
            throw new \DomainException('Missing mime type');
        }

        return $this->data->mime;
    }

    public function getCreated(): \DateTimeImmutable
    {
        if ($this->isStub) {
            throw new \DomainException('Data missing for stubs.');
        }
        if (!isset($this->data->timestamp)) {
            throw new \DomainException('Missing created date');
        }

        $timestamp = $this->data->timestamp;

        return new \DateTimeImmutable('@' . $timestamp);
    }

    public function getUrl(): string
    {
        if ($this->isStub) {
            throw new \DomainException('Data missing for stubs.');
        }
        if (!isset($this->data->url)) {
            throw new \DomainException('Missing URL');
        }

        return $this->data->url;
    }

    public function getParent(): ?DrupalOrgObject
    {
        return $this->parent;
    }

    public function setParent(DrupalOrgObject $object): DrupalOrgFile
    {
        $this->parent = $object;

        return $this;
    }

    public static function fromStub(\stdClass $data): DrupalOrgFile
    {
        if (!isset($data->id)) {
            throw new \InvalidArgumentException('ID is required');
        }

        $instance = new static((int) $data->id);
        $instance->stubData = $data;
        $instance->isStub = true;

        return $instance;
    }

    public static function fromResponse(ResponseInterface $response, DrupalOrgObjectRepository $repository): DrupalOrgFile
    {
        $data = json_decode((string) $response->getBody());
        $instance = new static((int) $data->fid);
        $instance
            ->setRepository($repository)
            ->importResponse($response);

        return $instance;
    }
}
