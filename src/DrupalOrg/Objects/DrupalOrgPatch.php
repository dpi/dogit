<?php

declare(strict_types=1);

namespace dogit\DrupalOrg\Objects;

class DrupalOrgPatch extends DrupalOrgFile
{
    protected string $version;
    protected ?string $patchContents;

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): DrupalOrgPatch
    {
        $this->version = $version;

        return $this;
    }

    public function getContents(): ?string
    {
        return $this->patchContents;
    }

    public function setContents(string $data): DrupalOrgPatch
    {
        $this->patchContents = $data;

        return $this;
    }

    public function getParent(): DrupalOrgComment
    {
        $parent = parent::getParent();
        assert($parent instanceof DrupalOrgComment);

        return $parent;
    }

    public static function fromFile(DrupalOrgFile $file): DrupalOrgPatch
    {
        if ($file->isStub) {
            $patch = static::fromStub($file->stubData);
        } else {
            $patch = new static($file->id());
            $patch->data = $file->data;
            $patch->isStub = false;
        }

        /** @var static $instance */
        $instance = $file->repository->share($patch);

        return $instance;
    }
}
