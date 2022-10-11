<?php

declare(strict_types=1);

namespace dogit\tests\DrupalOrg\Objects;

use dogit\DrupalOrg\DrupalOrgObjectRepository;
use dogit\DrupalOrg\Objects\DrupalOrgComment;
use dogit\DrupalOrg\Objects\DrupalOrgFile;
use dogit\tests\TestUtilities;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \dogit\DrupalOrg\Objects\DrupalOrgFile
 */
class DrupalOrgFileTest extends TestCase
{
    /**
     * @covers ::getMime
     */
    public function testGetMime(): void
    {
        $this->assertEquals(
            'text/x-diff',
            $this->createFile()->getMime()
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Data missing for stubs.');
        DrupalOrgFile::fromStub((object) ['id' => 1])->getMime();
    }

    /**
     * @covers ::getCreated
     */
    public function testGetCreated(): void
    {
        $this->assertEquals(
            1428704703,
            $this->createFile()->getCreated()->getTimestamp()
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Data missing for stubs.');
        DrupalOrgFile::fromStub((object) ['id' => 1])->getCreated();
    }

    /**
     * @covers ::getUrl
     */
    public function testGetUrl(): void
    {
        $this->assertEquals(
            'https://www.drupal.org/files/issues/alpha.patch',
            $this->createFile()->getUrl()
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Data missing for stubs.');
        DrupalOrgFile::fromStub((object) ['id' => 1])->getUrl();
    }

    /**
     * This is usually only set in the context of an issue, it is not known from API.
     *
     * @covers ::getParent
     * @covers ::setParent
     */
    public function testParent(): void
    {
        $comment = new DrupalOrgComment(66);

        $file = $this->createFile();
        $file->setParent($comment);
        /** @var \dogit\DrupalOrg\Objects\DrupalOrgComment $parent */
        $parent = $file->getParent();
        $this->assertEquals(66, $parent->id());

        $this->assertNull($this->createFile()->getParent());
    }

    /**
     * @covers ::fromStub
     */
    public function testFromStub(): void
    {
        $file = DrupalOrgFile::fromStub((object) ['id' => 1]);
        $this->assertEquals(1, $file->id());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ID is required');
        DrupalOrgFile::fromStub((object) [])->id();
    }

    /**
     * @covers ::fromResponse
     */
    public function testFromResponse(): void
    {
        $repository = new DrupalOrgObjectRepository();
        $file = DrupalOrgFile::fromResponse(
            new Response(200, [], TestUtilities::getFixture('file-22220001.json')),
            $repository,
        );
        $this->assertCount(0, iterator_to_array($repository->all(), false));
        $this->assertEquals(1428704703, $file->getCreated()->getTimestamp());
    }

    protected function createFile(?string $fixtureId = null): DrupalOrgFile
    {
        $fixtureId = $fixtureId ?? '22220001';
        $repository = new DrupalOrgObjectRepository();

        return DrupalOrgFile::fromResponse(
            new Response(200, [], TestUtilities::getFixture(sprintf('file-%s.json', $fixtureId))),
            $repository,
        );
    }
}
