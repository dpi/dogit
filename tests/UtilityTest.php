<?php

declare(strict_types=1);

namespace dogit\tests;

use dogit\DrupalOrg\DrupalApiInterface;
use dogit\DrupalOrg\DrupalOrgObjectIterator;
use dogit\DrupalOrg\DrupalOrgObjectRepository;
use dogit\DrupalOrg\IssueGraph\Events\CommentEvent;
use dogit\DrupalOrg\IssueGraph\Events\IssueEvent;
use dogit\DrupalOrg\IssueGraph\Events\IssueEventInterface;
use dogit\DrupalOrg\IssueGraph\Events\VersionChangeEvent;
use dogit\DrupalOrg\Objects\DrupalOrgComment;
use dogit\DrupalOrg\Objects\DrupalOrgFile;
use dogit\DrupalOrg\Objects\DrupalOrgIssue;
use dogit\Utility;
use GuzzleHttp\Psr7\Response;
use Http\Promise\Promise;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument\Token\AnyValuesToken;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;

/**
 * @coversDefaultClass \dogit\Utility
 */
class UtilityTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @covers ::filterCommentsWithPatches
     */
    public function testFilterCommentsWithPatches(): void
    {
        /** @var \Prophecy\Prophecy\ObjectProphecy|\dogit\DrupalOrg\DrupalApiInterface $api */
        $api = $this->prophesize(DrupalApiInterface::class);
        $api->getCommentAsync(new AnyValuesToken())
            ->willReturn($this->prophesize(Promise::class)->reveal());
        $api->getFileAsync(new AnyValuesToken())
            ->willReturn($this->prophesize(Promise::class)->reveal());

        $objectIterator = new DrupalOrgObjectIterator($api->reveal(), $this->createMock(LoggerInterface::class));

        $comments = [];

        // Comment with a patch.
        $comments[10] = DrupalOrgComment::fromResponse(new Response(200, [], (string) \json_encode(['cid' => '10'])), new DrupalOrgObjectRepository())
            ->setFiles([
                DrupalOrgFile::fromResponse(new Response(200, [], (string) \json_encode([
                    'fid' => '21',
                    'mime' => 'text/x-diff',
                ])), new DrupalOrgObjectRepository()),
            ]);

        // Comment with a patch and other file type.
        $comments[11] = DrupalOrgComment::fromResponse(new Response(200, [], (string) \json_encode(['cid' => '11'])), new DrupalOrgObjectRepository())
            ->setFiles([
                DrupalOrgFile::fromResponse(new Response(200, [], (string) \json_encode([
                    'fid' => '22',
                    'mime' => 'image/jpeg',
                ])), new DrupalOrgObjectRepository()),
                DrupalOrgFile::fromResponse(new Response(200, [], (string) \json_encode([
                    'fid' => '23',
                    'mime' => 'text/x-diff',
                ])), new DrupalOrgObjectRepository()),
            ]);

        // Comment with other file type, no patch.
        $comments[12] = DrupalOrgComment::fromResponse(new Response(200, [], (string) \json_encode(['cid' => '12'])), new DrupalOrgObjectRepository())
            ->setFiles([
                DrupalOrgFile::fromResponse(new Response(200, [], (string) \json_encode([
                    'fid' => '24',
                    'mime' => 'image/jpeg',
                ])), new DrupalOrgObjectRepository()),
            ]);

        // Comment no files.
        $comments[13] = DrupalOrgComment::fromResponse(new Response(200, [], (string) \json_encode(['cid' => '13'])), new DrupalOrgObjectRepository())
            ->setFiles([]);

        $result = Utility::filterCommentsWithPatches($objectIterator, $comments);
        $this->assertCount(2, $result);
        $this->assertEquals(10, $result[0][0]->id());
        $this->assertEquals(11, $result[1][0]->id());
        // Ensure original comment objects were not mutated.
        $this->assertCount(1, $comments[10]->getFiles());
        $this->assertCount(2, $comments[11]->getFiles());
    }

    /**
     * @covers ::getCommentsFromEvents
     */
    public function testGetCommentsFromEvents(): void
    {
        $events = [];

        $comment = $this->createMock(DrupalOrgComment::class);
        $comment->method('id')->willReturn(10);
        $event = $this->createMock(IssueEventInterface::class);
        $event->method('getComment')->willReturn($comment);
        $events[] = $event;

        $comment = $this->createMock(DrupalOrgComment::class);
        $comment->method('id')->willReturn(11);
        $event = $this->createMock(IssueEventInterface::class);
        $event->method('getComment')->willReturn($comment);
        $events[] = $event;

        $result = iterator_to_array(Utility::getCommentsFromEvents($events), false);
        $this->assertCount(2, $result);
        $this->assertEquals(10, $result[0]->id());
        $this->assertEquals(11, $result[1]->id());
    }

    /**
     * @covers ::getFilesFromComments
     */
    public function testGetFilesFromComments(): void
    {
        $comments = [];

        $comment = $this->createMock(DrupalOrgComment::class);
        $comment->method('id')->willReturn(10);
        $comment->method('getFiles')->willReturn([
            new DrupalOrgFile(20),
            new DrupalOrgFile(21),
        ]);
        $comments[] = $comment;

        $comment = $this->createMock(DrupalOrgComment::class);
        $comment->method('id')->willReturn(11);
        $comment->method('getFiles')->willReturn([
            new DrupalOrgFile(30),
        ]);
        $comments[] = $comment;

        $result = iterator_to_array(Utility::getFilesFromComments($comments), false);
        $this->assertCount(3, $result);
        $this->assertEquals(20, $result[0]->id());
        $this->assertEquals(21, $result[1]->id());
        $this->assertEquals(30, $result[2]->id());
    }

    /**
     * @covers ::deduplicateDrupalOrgObjects
     */
    public function testDeduplicateDrupalOrgObjects(): void
    {
        $objects = [];

        $object = $this->createMock(DrupalOrgComment::class);
        $object->method('id')->willReturn(1);
        $objects[] = $object;

        // This will be filtered out.
        $object = $this->createMock(DrupalOrgComment::class);
        $object->method('id')->willReturn(1);
        $objects[] = $object;

        // Create a object with same ID, different class, ensure it doesnt get
        // filtered out.
        $object = $this->createMock(DrupalOrgComment::class);
        $object->method('id')->willReturn(100);
        $objects[] = $object;

        $object = $this->createMock(DrupalOrgFile::class);
        $object->method('id')->willReturn(100);
        $objects[] = $object;

        $result = Utility::deduplicateDrupalOrgObjects($objects);
        $this->assertCount(3, $result);
        $this->assertEquals(1, $result[0]->id());
        $this->assertEquals(100, $result[2]->id());
        $this->assertInstanceOf(DrupalOrgComment::class, $result[2]);
        $this->assertEquals(100, $result[3]->id());
        $this->assertInstanceOf(DrupalOrgFile::class, $result[3]);
    }

    /**
     * @covers ::versionAt
     */
    public function testVersionAt(): void
    {
        $events[] = new VersionChangeEvent(DrupalOrgComment::fromResponse(new Response(200, [], (string) \json_encode([
            'cid' => '10',
            'created' => (new \DateTimeImmutable('1 June 1996'))->getTimestamp(),
        ])), new DrupalOrgObjectRepository()), '1.0.0', '1.1.0');
        $events[] = new VersionChangeEvent(DrupalOrgComment::fromResponse(new Response(200, [], (string) \json_encode([
            'cid' => '10',
            'created' => (new \DateTimeImmutable('1 July 1996'))->getTimestamp(),
        ])), new DrupalOrgObjectRepository()), '1.2.0', '1.7.0');
        $events[] = new VersionChangeEvent(DrupalOrgComment::fromResponse(new Response(200, [], (string) \json_encode([
            'cid' => '10',
            'created' => (new \DateTimeImmutable('1 August 1996'))->getTimestamp(),
        ])), new DrupalOrgObjectRepository()), '1.7.0', '1.8.0');

        $api = $this->prophesize(DrupalApiInterface::class);
        $objectIterator = new DrupalOrgObjectIterator($api->reveal(), $this->createMock(LoggerInterface::class));
        $this->assertEquals('1.1.0', Utility::versionAt($objectIterator, new \DateTimeImmutable('2 June 1996'), $events));
        $this->assertEquals('1.7.0', Utility::versionAt($objectIterator, new \DateTimeImmutable('2 July 1996'), $events));
        $this->assertEquals('1.8.0', Utility::versionAt($objectIterator, new \DateTimeImmutable('2 December 1996'), $events));
    }

    /**
     * @covers ::versionAt
     */
    public function testVersionAtBeforeFirstComment(): void
    {
        $events[] = new VersionChangeEvent(DrupalOrgComment::fromResponse(new Response(200, [], (string) \json_encode([
            'cid' => '10',
            'created' => (new \DateTimeImmutable('1 June 1996'))->getTimestamp(),
        ])), new DrupalOrgObjectRepository()), '1.0.0', '1.1.0');

        $api = $this->prophesize(DrupalApiInterface::class);
        $objectIterator = new DrupalOrgObjectIterator($api->reveal(), $this->createMock(LoggerInterface::class));
        $result = Utility::versionAt($objectIterator, new \DateTimeImmutable('1 January 1996'), $events);
        $this->assertEquals('1.1.0', $result);
    }

    /**
     * @covers ::ensureInitialVersionChange
     * @covers ::versionAt
     */
    public function testVersionAtBeforeVersionChangeEvent(): void
    {
        $comment1 = DrupalOrgComment::fromResponse(new Response(200, [], (string) \json_encode([
            'cid' => '1',
            'created' => (new \DateTimeImmutable('1 May 1996'))->getTimestamp(),
        ])), new DrupalOrgObjectRepository());
        $events[] = new CommentEvent($comment1);
        $comment2 = DrupalOrgComment::fromResponse(new Response(200, [], (string) \json_encode([
            'cid' => '2',
            'created' => (new \DateTimeImmutable('1 June 1996'))->getTimestamp(),
        ])), new DrupalOrgObjectRepository());
        $events[] = new VersionChangeEvent($comment2, '1.0.0', '1.1.0');

        $issue = $this->createMock(DrupalOrgIssue::class);
        $issue->method('getComments')->willReturn([
            $comment1,
            $comment2,
        ]);
        $this->assertCount(2, $events);
        $events = Utility::ensureInitialVersionChange($events, $issue);
        $this->assertCount(3, $events);
        $versionChangeEvents = IssueEvent::filterVersionChangeEvents($events);

        $api = $this->prophesize(DrupalApiInterface::class);
        $objectIterator = new DrupalOrgObjectIterator($api->reveal(), $this->createMock(LoggerInterface::class));
        $this->assertEquals('1.0.0', Utility::versionAt($objectIterator, new \DateTimeImmutable('2 May 1996'), $versionChangeEvents));
    }

    /**
     * @covers ::ensureInitialVersionChange
     * @covers ::versionAt
     */
    public function testVersionAtNoVersionChangeEvents(): void
    {
        $comment1 = DrupalOrgComment::fromResponse(new Response(200, [], (string) \json_encode([
            'cid' => '1',
            'created' => (new \DateTimeImmutable('1 May 1996'))->getTimestamp(),
        ])), new DrupalOrgObjectRepository());
        $events[] = new CommentEvent($comment1);

        $issue = $this->createMock(DrupalOrgIssue::class);
        $issue->method('getComments')->willReturn([
            $comment1,
        ]);
        $issue->method('getCurrentVersion')->willReturn('1.0.0');
        $this->assertCount(1, $events);
        $events = Utility::ensureInitialVersionChange($events, $issue);
        $this->assertCount(2, $events);
        $versionChangeEvents = IssueEvent::filterVersionChangeEvents($events);

        $api = $this->prophesize(DrupalApiInterface::class);
        $objectIterator = new DrupalOrgObjectIterator($api->reveal(), $this->createMock(LoggerInterface::class));
        $this->assertEquals('1.0.0', Utility::versionAt($objectIterator, new \DateTimeImmutable('2 May 1996'), $versionChangeEvents));
    }

    /**
     * @covers ::numericConstraintRuleBuilder
     * @dataProvider provider_numericConstraintRuleBuilder
     */
    public function testNumericConstraintRuleBuilder(string $constraint, int $number, ?bool $assertMatches): void
    {
        if (!isset($assertMatches)) {
            $this->expectException(\InvalidArgumentException::class);
        }

        $filters = Utility::numericConstraintRuleBuilder([$constraint]);
        // One constraint passed in so 0-1 filters expected:
        $this->assertCount(1, $filters);
        /** @var callable $filter */
        $filter = reset($filters);

        if (isset($assertMatches)) {
            $this->assertEquals($assertMatches, $filter($number));
        }
    }

    /**
     * @return array<string, array>
     */
    public function provider_numericConstraintRuleBuilder(): array
    {
        return [
            'verbatim fail' => ['100', 90, false],
            'verbatim match' => ['100', 100, true],
            'lte fail' => ['<=100', 101, false],
            'lte match eq' => ['<=100', 100, true],
            'lte match less' => ['<=100', 99, true],
            'gte fail' => ['>=100', 99, false],
            'gte match eq' => ['>=100', 100, true],
            'gte match less' => ['>=100', 101, true],
            'lt fail' => ['<100', 100, false],
            'lt match' => ['<100', 99, true],
            'gt fail' => ['>100', 100, false],
            'gt match' => ['>100', 101, true],
            'noteq1 fail' => ['!=100', 100, false],
            'noteq1 match' => ['!=100', 101, true],
            'noteq2 fail' => ['<>100', 100, false],
            'noteq2 match' => ['<>100', 101, true],
            'eq fail' => ['=100', 99, false],
            'eq match' => ['=100', 100, true],

            // Invalid constraint throws exceptions:
            'invalid constraint 1' => ['<<=100', 100, null],
            'invalid constraint 2' => ['>>=100', 100, null],
            'invalid constraint 3' => ['<<100', 100, null],
            'invalid constraint 4' => ['>>100', 100, null],
            'invalid constraint 5' => ['!100', 100, null],
            'invalid constraint 6' => ['!==100', 100, null],
            'invalid constraint 7' => ['!===100', 100, null],
            'invalid constraint 8' => ['==100', 100, null],
            'invalid constraint 9' => ['===100', 100, null],
            'invalid constraint 10' => ['===', 100, null],
            'invalid constraint 11' => ['*', 100, null],
            'invalid constraint 12' => ['10=', 100, null],
            'invalid constraint 13' => ['<=', 100, null],
            'invalid constraint 14' => ['>=', 100, null],
            'invalid constraint 15' => ['<', 100, null],
            'invalid constraint 16' => ['>', 100, null],
            'invalid constraint 17' => ['!=', 100, null],
            'invalid constraint 18' => ['<>', 100, null],
            'invalid constraint 19' => ['=', 100, null],
        ];
    }

    /**
     * @covers ::normalizeGitReferenceVersion
     */
    public function testNormalizeGitReferenceVersion(): void
    {
        $this->assertEquals('8.0.x', Utility::normalizeGitReferenceVersion('8.x'));
        $this->assertEquals('8.1.x', Utility::normalizeGitReferenceVersion('8.1.x'));
    }

    /**
     * @covers ::normalizeSemverVersion
     */
    public function testNormalizeSemverVersion(): void
    {
        $this->assertEquals('8.0.x', Utility::normalizeSemverVersion('8.x'));
        $this->assertEquals('1.x', Utility::normalizeSemverVersion('8.x-1.x'));
        $this->assertEquals('2.x', Utility::normalizeSemverVersion('2.x'));
    }

    /**
     * @covers ::drupalProjectNameFromComposerJson
     */
    public function testProjectNameComposerJson(): void
    {
        $finder = new Finder();
        vfsStream::setup('test', structure: [
            'composer.json' => file_get_contents(__DIR__ . '/fixtures/composerFiles/composer.json'),
        ]);
        $this->assertEquals('foo', Utility::drupalProjectNameFromComposerJson('vfs://test/', $finder));
    }

    /**
     * @covers ::drupalProjectNameFromComposerJson
     */
    public function testProjectNameComposerJsonMalformed(): void
    {
        $finder = new Finder();
        vfsStream::setup('test', structure: [
            'composer.json' => file_get_contents(__DIR__ . '/fixtures/composerFiles/composerMalformed.json'),
        ]);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to parse composer.json: Syntax error');
        Utility::drupalProjectNameFromComposerJson('vfs://test/', $finder);
    }

    /**
     * @covers ::drupalProjectNameFromComposerJson
     */
    public function testProjectNameComposerJsonMissingName(): void
    {
        $finder = new Finder();
        vfsStream::setup('test', structure: [
            'composer.json' => file_get_contents(__DIR__ . '/fixtures/composerFiles/composerMissingName.json'),
        ]);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing Composer project name');
        Utility::drupalProjectNameFromComposerJson('vfs://test/', $finder);
    }

    /**
     * @covers ::drupalProjectNameFromComposerJson
     */
    public function testProjectNameComposerJsonNotDrupalNamespace(): void
    {
        $finder = new Finder();
        vfsStream::setup('test', structure: [
            'composer.json' => file_get_contents(__DIR__ . '/fixtures/composerFiles/composerNotDrupal.json'),
        ]);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Project is not in the Drupal namespace');
        Utility::drupalProjectNameFromComposerJson('vfs://test/', $finder);
    }
}
