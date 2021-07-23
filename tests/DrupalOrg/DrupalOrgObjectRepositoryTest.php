<?php

declare(strict_types=1);

namespace dogit\tests\DrupalOrg;

use dogit\DrupalOrg\DrupalOrgObjectRepository;
use dogit\DrupalOrg\Objects\DrupalOrgIssue;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \dogit\DrupalOrg\DrupalOrgObjectRepository
 */
final class DrupalOrgObjectRepositoryTest extends TestCase
{
    /**
     * @covers ::share
     */
    public function testShare(): void
    {
        $repository = new DrupalOrgObjectRepository();
        $this->assertCount(0, iterator_to_array($repository->all(), false));

        $object1 = DrupalOrgIssue::fromStub((object) ['id' => 1]);
        $hash1 = spl_object_hash($object1);
        $repository->share($object1);
        $this->assertCount(1, iterator_to_array($repository->all(), false));

        $object2 = DrupalOrgIssue::fromStub((object) ['id' => 2]);
        $repository->share($object2);
        $this->assertCount(2, iterator_to_array($repository->all(), false));

        $object3 = DrupalOrgIssue::fromStub((object) ['id' => 1]);
        $hash3 = spl_object_hash($object3);
        $this->assertNotEquals($hash3, $hash1);
        $this->assertEquals($object1->id(), $object3->id());
        // We assert here that when object3 has the same ID as object1 that:
        //  - object1 is returned
        //  - object3 is not added to the repository/collections.
        $object3return = $repository->share($object3);
        $this->assertCount(2, iterator_to_array($repository->all(), false));
        $this->assertEquals($hash1, spl_object_hash($object3return));
    }

    /**
     * @covers ::all
     */
    public function testAll(): void
    {
        $repository = new DrupalOrgObjectRepository();
        $ref1 = $repository->share(DrupalOrgIssue::fromStub((object) ['id' => 1]));
        $ref2 = $repository->share(DrupalOrgIssue::fromStub((object) ['id' => 2]));
        $ref3 = $repository->share(DrupalOrgIssue::fromStub((object) ['id' => 3]));
        $ref4 = $repository->share(DrupalOrgIssue::fromStub((object) ['id' => 1]));
        $this->assertCount(3, iterator_to_array($repository->all(), false));

        // No references remaining cause WeakMap to release the object.
        unset($ref3);
        $this->assertCount(2, iterator_to_array($repository->all(), false));
    }
}
