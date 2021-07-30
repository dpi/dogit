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
        !$this->isStub ?: throw new \DomainException('Data missing for stubs.');

        return $this->data->mime ?? throw new \DomainException('Missing mime type');
    }

    public function getCreated(): \DateTimeImmutable
    {
        !$this->isStub ?: throw new \DomainException('Data missing for stubs.');
        $timestamp = $this->data->timestamp ?? throw new \DomainException('Missing created date');

        return new \DateTimeImmutable('@' . $timestamp);
    }

    public function getUrl(): string
    {
        !$this->isStub ?: throw new \DomainException('Data missing for stubs.');

        return $this->data->url ?? throw new \DomainException('Missing URL');
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
        $data->id ?? throw new \InvalidArgumentException('ID is required');
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
