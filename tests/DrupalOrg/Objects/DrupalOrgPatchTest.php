<?php

declare(strict_types=1);

namespace dogit\tests\DrupalOrg\Objects;

use dogit\DrupalOrg\DrupalOrgObjectRepository;
use dogit\DrupalOrg\Objects\DrupalOrgComment;
use dogit\DrupalOrg\Objects\DrupalOrgFile;
use dogit\DrupalOrg\Objects\DrupalOrgPatch;
use dogit\tests\TestUtilities;
use GuzzleHttp\Psr7\Response;

/**
 * @coversDefaultClass \dogit\DrupalOrg\Objects\DrupalOrgPatch
 */
class DrupalOrgPatchTest extends DrupalOrgFileTest
{
    /**
     * @covers ::getVersion
     * @covers ::setVersion
     */
    public function testVersion(): void
    {
        $patch = $this->createFile();
        $patch->setVersion('13.3.7');
        $this->assertEquals('13.3.7', $patch->getVersion());
    }

    /**
     * @covers ::getContents
     * @covers ::setContents
     */
    public function testContents(): void
    {
        $patch = $this->createFile();
        $patch->setContents('foo');
        $this->assertEquals('foo', $patch->getContents());
    }

    /**
     * @covers ::fromFile
     */
    public function testFromFile(): void
    {
        $file = DrupalOrgFile::fromStub((object) [
            'id' => 33,
        ]);
        $file->setRepository(new DrupalOrgObjectRepository());

        $patch = DrupalOrgPatch::fromFile($file);
        $this->assertInstanceOf(DrupalOrgPatch::class, $patch);
        $this->assertEquals(33, $patch->id());
    }

    public function testParent(): void
    {
        $comment = new DrupalOrgComment(66);

        $file = $this->createFile();
        $file->setParent($comment);
        /** @var \dogit\DrupalOrg\Objects\DrupalOrgComment $parent */
        $parent = $file->getParent();
        $this->assertEquals(66, $parent->id());
    }

    protected function createFile(?string $fixtureId = null): DrupalOrgPatch
    {
        $fixtureId = $fixtureId ?? '22220001';
        $repository = new DrupalOrgObjectRepository();

        return DrupalOrgPatch::fromResponse(
            new Response(200, [], TestUtilities::getFixture(sprintf('file-%s.json', $fixtureId))),
            $repository,
        );
    }
}
