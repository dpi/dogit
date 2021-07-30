<?php

declare(strict_types=1);

namespace dogit\DrupalOrg;

use dogit\DrupalOrg\Objects\DrupalOrgObject;

final class DrupalOrgObjectRepository
{
    /**
     * @var \dogit\DrupalOrg\DrupalOrgObjectCollection<\dogit\DrupalOrg\Objects\DrupalOrgObject>[]
     */
    private array $collections = [];

    /**
     * @template T of \dogit\DrupalOrg\Objects\DrupalOrgObject
     *
     * @param T $object
     *
     * @return T
     *   The returned object type will be the same type as the one passed in.
     *   The object will either be a reference to the same object if it doesn't
     *   exist in the repo, or an existing object from the repository will be
     *   returned instead.
     */
    public function share(DrupalOrgObject $object): DrupalOrgObject
    {
        /** @var \dogit\DrupalOrg\DrupalOrgObjectCollection<T> $collection */
        $collection = $this->collections[get_class($object)] ?? (
            $this->collections[get_class($object)] = new DrupalOrgObjectCollection()
        );

        $object = $collection->share($object);
        $object->setRepository($this);

        return $object;
    }

    /**
     * Get all objects in all collections.
     *
     * @return \Generator<int, \dogit\DrupalOrg\Objects\DrupalOrgObject>
     *   Yields instances of \dogit\DrupalOrg\Objects\DrupalOrgObject
     */
    public function all(): \Generator
    {
        foreach ($this->collections as $collection) {
            yield from $collection->all();
        }
    }
}
