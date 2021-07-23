<?php

declare(strict_types=1);

namespace dogit\tests\DrupalOrg\Objects;

use dogit\DrupalOrg\DrupalOrgObjectIterator;
use dogit\DrupalOrg\DrupalOrgObjectRepository;
use dogit\DrupalOrg\Objects\DrupalOrgComment;
use dogit\DrupalOrg\Objects\DrupalOrgFile;
use dogit\DrupalOrg\Objects\DrupalOrgIssue;
use dogit\tests\TestUtilities;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument\Token\AnyValuesToken;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @coversDefaultClass \dogit\DrupalOrg\Objects\DrupalOrgIssue
 */
class DrupalOrgIssueTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @covers ::getCreated
     */
    public function testGetCreated(): void
    {
        $this->assertEquals(
            1412524021,
            $this->createIssue()->getCreated()->getTimestamp()
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Data missing for stubs.');
        (DrupalOrgIssue::fromStub((object) ['id' => 1]))->getCreated();
    }

    /**
     * @covers ::getCurrentVersion
     */
    public function testGetCurrentVersion(): void
    {
        $this->assertEquals('9.3.x-dev', $this->createIssue()->getCurrentVersion());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Data missing for stubs.');
        (DrupalOrgIssue::fromStub((object) ['id' => 1]))->getCurrentVersion();
    }

    /**
     * @covers ::commentsWithFiles
     */
    public function testCommentsWithFiles(): void
    {
        $comments = $this->createIssue()->commentsWithFiles();
        $this->assertCount(3, $comments);
        assert(3 === count($comments));
        $this->assertEquals(13370001, $comments[0]->id());
        $this->assertEquals(13370005, $comments[1]->id());
        $this->assertEquals(13370007, $comments[2]->id());
    }

    /**
     * @covers ::getProjectName
     */
    public function testGetProjectName(): void
    {
        $this->assertEquals('drupal', $this->createIssue()->getProjectName());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Data missing for stubs.');
        (DrupalOrgIssue::fromStub((object) ['id' => 1]))->getProjectName();
    }

    /**
     * @covers ::getTitle
     */
    public function testGetTitle(): void
    {
        $this->assertEquals('[PP-1] Implement a generic revision UI', $this->createIssue()->getTitle());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Data missing for stubs.');
        (DrupalOrgIssue::fromStub((object) ['id' => 1]))->getTitle();
    }

    /**
     * @covers ::getComments
     */
    public function testGetComments(): void
    {
        $comments = $this->createIssue()->getComments();
        $this->assertCount(8, $comments);
        assert(8 === count($comments));
        $this->assertEquals(13370001, $comments[0]->id());
        $this->assertEquals(13370002, $comments[1]->id());
        $this->assertEquals(13370003, $comments[2]->id());
        $this->assertEquals(13370004, $comments[3]->id());
        $this->assertEquals(13370005, $comments[4]->id());
        $this->assertEquals(13370006, $comments[5]->id());
        $this->assertEquals(13370007, $comments[6]->id());
        $this->assertEquals(13370008, $comments[7]->id());
    }

    /**
     * @covers ::getFiles
     */
    public function testGetFiles(): void
    {
        $this->assertEquals([
            22220001,
            22220002,
            22220003,
            22220004,
        ], $this->createIssue()->getFiles());
    }

    /**
     * @covers ::getPatches
     */
    public function testGetPatches(): void
    {
        $issue = $this->createIssue();
        $objectIterator = $this->prophesize(DrupalOrgObjectIterator::class);
        $objectIterator->unstubComments(new AnyValuesToken())
            ->shouldBeCalledTimes(1)
            ->will(function ($args) {
                return array_map(function (DrupalOrgComment $comment) {
                    $response = new Response(200, [], TestUtilities::getFixture(sprintf('comment-%s.json', $comment->id())));
                    $comment->importResponse($response);

                    return $comment;
                }, $args[0]);
            });
        $objectIterator->unstubFiles(new AnyValuesToken())
            ->shouldBeCalledTimes(1)
            ->will(function ($args) {
                return array_map(function (DrupalOrgFile $file) {
                    $response = new Response(200, [], TestUtilities::getFixture(sprintf('file-%s.json', $file->id())));
                    $file->importResponse($response);

                    return $file;
                }, $args[0]);
            });

        $patches = iterator_to_array($issue->getPatches($objectIterator->reveal()), false);

        $this->assertCount(3, $patches);
    }

    /**
     * @covers ::fromStub
     */
    public function testFromStub(): void
    {
        $object = DrupalOrgIssue::fromStub((object) ['id' => 1]);
        $this->assertEquals(1, $object->id());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ID is required');
        DrupalOrgIssue::fromStub((object) [])->id();
    }

    /**
     * @covers ::fromResponse
     */
    public function testFromResponse(): void
    {
        $repository = new DrupalOrgObjectRepository();
        $object = DrupalOrgIssue::fromResponse(
            new Response(200, [], TestUtilities::getFixture('issue.json')),
            $repository,
        );
        $this->assertCount(0, iterator_to_array($repository->all(), false));
        $this->assertEquals(1412524021, $object->getCreated()->getTimestamp());
    }

    protected function createIssue(): DrupalOrgIssue
    {
        $repository = new DrupalOrgObjectRepository();

        return DrupalOrgIssue::fromResponse(
            new Response(200, [], TestUtilities::getFixture('issue.json')),
            $repository,
        );
    }
}
