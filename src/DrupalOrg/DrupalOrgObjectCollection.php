<?php

declare(strict_types=1);

namespace dogit\DrupalOrg;

use dogit\DrupalOrg\Objects\DrupalOrgObject;

/**
 * @template T of \dogit\DrupalOrg\Objects\DrupalOrgObject
 */
class DrupalOrgObjectCollection
{
    /**
     * @var \WeakMap<T, int>
     */
    private \WeakMap $collection;

    public function __construct()
    {
        $this->collection = new \WeakMap();
    }

    /**
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
        $objectKey = $object->id();
        foreach ($this->collection as $repoObject => $repoKey) {
            if ($objectKey === $repoKey) {
                return $repoObject;
            }
        }

        $this->collection[$object] = $objectKey;

        return $object;
    }

    /**
     * @return \Generator<int,T>
     */
    public function all(): \Generator
    {
        foreach ($this->collection as $repoObject => $repoKey) {
            yield $repoObject;
        }
    }
}
