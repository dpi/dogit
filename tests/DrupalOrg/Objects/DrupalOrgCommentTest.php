<?php

declare(strict_types=1);

namespace dogit\tests\DrupalOrg\Objects;

use dogit\DrupalOrg\DrupalOrgObjectRepository;
use dogit\DrupalOrg\Objects\DrupalOrgComment;
use dogit\tests\TestUtilities;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \dogit\DrupalOrg\Objects\DrupalOrgComment
 */
final class DrupalOrgCommentTest extends TestCase
{
    /**
     * @covers ::getCreated
     */
    public function testGetCreated(): void
    {
        $this->assertEquals(
            1400010000,
            $this->createComment()->getCreated()->getTimestamp()
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Data missing for stubs.');
        (DrupalOrgComment::fromStub((object) ['id' => 1]))->getCreated();
    }

    /**
     * @covers ::getFiles
     */
    public function testGetFiles(): void
    {
        $files = $this->createComment()->getFiles();
        $this->assertCount(1, $files);
        assert(count($files) > 0);
        $this->assertEquals(22220001, reset($files)->id());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Data missing for stubs.');
        (DrupalOrgComment::fromStub((object) ['id' => 1]))->getFiles();
    }

    /**
     * @covers ::setFiles
     */
    public function testSetFiles(): void
    {
        $comment = $this->createComment();
        $this->assertCount(1, $comment->getFiles());
        $comment->setFiles([]);
        $this->assertCount(0, $comment->getFiles());
    }

    /**
     * @covers ::isBot
     */
    public function testIsBot(): void
    {
        $comment = $this->createComment('13370001');
        $this->assertFalse($comment->isBot());

        $comment = $this->createComment('13370009');
        $this->assertTrue($comment->isBot());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Data missing for stubs.');
        (DrupalOrgComment::fromStub((object) ['id' => 1]))->isBot();
    }

    /**
     * @covers ::getAuthorName
     */
    public function testGetAuthorName(): void
    {
        $comment = $this->createComment();
        $this->assertEquals('larowlan', $comment->getAuthorName());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Data missing for stubs.');
        (DrupalOrgComment::fromStub((object) ['id' => 1]))->getAuthorName();
    }

    /**
     * @covers ::getAuthorId
     */
    public function testGetAuthorId(): void
    {
        $comment = $this->createComment();
        $this->assertEquals(395439, $comment->getAuthorId());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Data missing for stubs.');
        (DrupalOrgComment::fromStub((object) ['id' => 1]))->getAuthorId();
    }

    /**
     * @covers ::getIssue
     */
    public function testGetIssue(): void
    {
        $comment = $this->createComment();
        $issue = $comment->getIssue();
        $this->assertEquals(2350939, $issue->id());
        $this->assertTrue($issue->isStub());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Data missing for stubs.');
        (DrupalOrgComment::fromStub((object) ['id' => 1]))->getIssue();
    }

    /**
     * This is usually only set in the context of an issue, it is not known from API.
     *
     * @covers ::getSequence
     * @covers ::setSequence
     */
    public function testSequence(): void
    {
        $comment = $this->createComment();
        $comment->setSequence(33);
        $this->assertEquals(33, $comment->getSequence());

        $this->expectException(\Error::class);
        $this->createComment()->getSequence();
    }

    /**
     * @covers ::getComment
     */
    public function testGetComment(): void
    {
        $this->assertEquals('Comment text', $this->createComment()->getComment());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Data missing for stubs.');
        (DrupalOrgComment::fromStub((object) ['id' => 1]))->getComment();
    }

    /**
     * @covers ::importResponse
     */
    public function testImportResponse(): void
    {
        $comment = DrupalOrgComment::fromStub((object) ['id' => 1]);
        $comment->setRepository(new DrupalOrgObjectRepository());
        $comment->importResponse(new Response(200, [], TestUtilities::getFixture('comment-13370001.json')));
        $this->assertEquals(1400010000, $comment->getCreated()->getTimestamp());
    }

    /**
     * @covers ::fromStub
     */
    public function testFromStub(): void
    {
        $comment = DrupalOrgComment::fromStub((object) ['id' => 1]);
        $this->assertEquals(1, $comment->id());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ID is required');
        DrupalOrgComment::fromStub((object) [])->id();
    }

    /**
     * @covers ::fromResponse
     */
    public function testFromResponse(): void
    {
        $repository = new DrupalOrgObjectRepository();
        $comment = DrupalOrgComment::fromResponse(
            new Response(200, [], TestUtilities::getFixture('comment-13370001.json')),
            $repository,
        );
        $this->assertCount(1, iterator_to_array($repository->all(), false));
        $this->assertEquals(1400010000, $comment->getCreated()->getTimestamp());
    }

    protected function createComment(?string $commentFixtureId = null): DrupalOrgComment
    {
        $commentFixtureId = $commentFixtureId ?? '13370001';
        $repository = new DrupalOrgObjectRepository();

        return DrupalOrgComment::fromResponse(
            new Response(200, [], TestUtilities::getFixture(sprintf('comment-%s.json', $commentFixtureId))),
            $repository,
        );
    }
}
