<?php

declare(strict_types=1);

namespace dogit\tests\DrupalOrg\IssueGraph;

use dogit\DrupalOrg\DrupalOrgObjectRepository;
use dogit\DrupalOrg\IssueGraph\Events\AssignmentChangeEvent;
use dogit\DrupalOrg\IssueGraph\Events\CommentEvent;
use dogit\DrupalOrg\IssueGraph\Events\IssueEvent;
use dogit\DrupalOrg\IssueGraph\Events\MergeRequestCreateEvent;
use dogit\DrupalOrg\IssueGraph\Events\StatusChangeEvent;
use dogit\DrupalOrg\IssueGraph\Events\TestResultEvent;
use dogit\DrupalOrg\IssueGraph\Events\VersionChangeEvent;
use dogit\DrupalOrg\Objects\DrupalOrgComment;
use dogit\tests\TestUtilities;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Covers events.
 */
final class DrupalOrgIssueGraphEventsTest extends TestCase
{
    /**
     * @covers \dogit\DrupalOrg\IssueGraph\Events\AssignmentChangeEvent
     */
    public function testAssignmentChangeEvent(): void
    {
        $comment = DrupalOrgComment::fromStub((object) ['id' => 1]);
        $from = 'John ';
        $to = '» Jane';
        $event = new AssignmentChangeEvent($comment, $from, $to);

        $this->assertEquals('John', $event->from());
        $this->assertEquals('Jane', $event->to());
        $this->assertEquals('Assignment change from John to Jane', (string) $event);
    }

    /**
     * @covers \dogit\DrupalOrg\IssueGraph\Events\CommentEvent
     */
    public function testCommentEvent(): void
    {
        $comment = DrupalOrgComment::fromResponse(
            new Response(200, [], TestUtilities::getFixture('comment-13370001.json')),
            new DrupalOrgObjectRepository(),
        );
        $event = new CommentEvent($comment);

        $this->assertEquals('🗣 Comment by 👤 larowlan on Tue, 13 May 2014 19:40:00 +0000', (string) $event);
    }

    /**
     * @covers \dogit\DrupalOrg\IssueGraph\Events\IssueEvent
     * @covers \dogit\DrupalOrg\IssueGraph\Events\IssueEvent::__construct
     * @covers \dogit\DrupalOrg\IssueGraph\Events\IssueEvent::__toString
     * @covers \dogit\DrupalOrg\IssueGraph\Events\IssueEvent::getData
     */
    public function testIssueEvent(): void
    {
        $comment = DrupalOrgComment::fromStub((object) ['id' => 1]);
        $event = new IssueEvent($comment, ['foo' => 'bar']);

        $this->assertEquals('Generic event', (string) $event);
        $this->assertEquals(['foo' => 'bar'], $event->getData());
    }

    /**
     * @param mixed[] $data
     * @param class-string $expectedClass
     *
     * @covers \dogit\DrupalOrg\IssueGraph\Events\IssueEvent::fromRaw
     *
     * @dataProvider issueEventFromRawProvider
     */
    public function testIssueEventFromRaw(string $type, array $data, string $expectedClass, string $expectedString): void
    {
        $comment = DrupalOrgComment::fromStub((object) ['id' => 1]);
        $event = IssueEvent::fromRaw($comment, $type, $data);
        $this->assertInstanceOf($expectedClass, $event);
        $this->assertEquals($expectedString, (string) $event);
    }

    /**
     * @return array<string, array{string, array{0: string, 1: string}, class-string, string}>
     */
    public function issueEventFromRawProvider(): array
    {
        return [
            'StatusChangeEvent' => [
                'Status',
                ['Active ', '» Needs review'],
                StatusChangeEvent::class,
                'Status change from Active to Needs review',
            ],
            'VersionChangeEvent' => [
                'Version',
                ['8.0.x-dev ', '» 8.1.x-dev'],
                VersionChangeEvent::class,
                'Version changed from 8.0.x to 8.1.x',
            ],
            'AssignmentChangeEvent' => [
                'Assigned',
                ['John ', '» Jane'],
                AssignmentChangeEvent::class,
                'Assignment change from John to Jane',
            ],
            'Issue tags' => [
                'Issue tags',
                ['', '+Workflow Initiative'],
                IssueEvent::class,
                'Generic event',
            ],
            'Related issues' => [
                'Related issues',
                ['', '+#2452523: [Meta] Offer a revisions tab for all entities'],
                IssueEvent::class,
                'Generic event',
            ],
            'Other' => [
                'Blah blah',
                ['abc', 'lmno', 'xyz'],
                IssueEvent::class,
                'Generic event',
            ],
        ];
    }

    /**
     * @covers \dogit\DrupalOrg\IssueGraph\Events\IssueEvent::filterMergeRequestCreateEvents
     */
    public function testFilterMergeRequestCreateEvents(): void
    {
        $comment = DrupalOrgComment::fromStub((object) ['id' => 1]);
        $events = [
            new MergeRequestCreateEvent($comment, '', '', 1, '', '', ''),
            new IssueEvent($comment, []),
            new MergeRequestCreateEvent($comment, '', '', 1, '', '', ''),
            new IssueEvent($comment, []),
            new MergeRequestCreateEvent($comment, '', '', 1, '', '', ''),
        ];
        $actual = IssueEvent::filterMergeRequestCreateEvents($events);
        $this->assertCount(3, $actual);
        $actual = array_values($actual);
        $this->assertInstanceOf(MergeRequestCreateEvent::class, $actual[0]);
        $this->assertInstanceOf(MergeRequestCreateEvent::class, $actual[1]);
        $this->assertInstanceOf(MergeRequestCreateEvent::class, $actual[2]);
    }

    /**
     * @covers \dogit\DrupalOrg\IssueGraph\Events\IssueEvent::filterVersionChangeEvents
     */
    public function testFilterVersionChangeEvents(): void
    {
        $comment = DrupalOrgComment::fromStub((object) ['id' => 1]);
        $events = [
            new VersionChangeEvent($comment, '1.0.0', '2.0.0'),
            new IssueEvent($comment, []),
            new VersionChangeEvent($comment, '2.0.0', '3.0.0'),
            new IssueEvent($comment, []),
            new VersionChangeEvent($comment, '3.0.0', '4.0.0'),
        ];
        $actual = IssueEvent::filterVersionChangeEvents($events);
        $this->assertCount(3, $actual);
        $actual = array_values($actual);
        $this->assertInstanceOf(VersionChangeEvent::class, $actual[0]);
        $this->assertInstanceOf(VersionChangeEvent::class, $actual[1]);
        $this->assertInstanceOf(VersionChangeEvent::class, $actual[2]);
    }

    /**
     * @covers \dogit\DrupalOrg\IssueGraph\Events\IssueEventTrait::getComment
     */
    public function testTraitGetComment(): void
    {
        $comment = DrupalOrgComment::fromStub((object) ['id' => 1]);
        $event = new IssueEvent($comment, ['foo' => 'bar']);
        $this->assertEquals(1, $event->getComment()->id());
    }

    /**
     * @covers \dogit\DrupalOrg\IssueGraph\Events\MergeRequestCreateEvent
     */
    public function testMergeRequestCreateEvent(): void
    {
        $comment = DrupalOrgComment::fromStub((object) ['id' => 1]);
        $event = new MergeRequestCreateEvent(
            $comment,
            'http://merge.request.url',
            'myproject',
            33,
            'git@github.com:foo/bar.git',
            'https://github.com/foo/bar.git',
            'my-branch',
        );

        $this->assertEquals(33, $event->mergeRequestId());
        $this->assertEquals('http://merge.request.url', $event->mergeRequestUrl());
        $this->assertEquals('myproject', $event->project());
        $this->assertEquals('git@github.com:foo/bar.git', $event->getGitUrl());
        $this->assertEquals('https://github.com/foo/bar.git', $event->getGitHttpUrl());
        $this->assertEquals('my-branch', $event->getGitBranch());
        $this->assertEquals('git clone -b my-branch git@github.com:foo/bar.git', $event->getCloneCommand());
        $this->assertEquals('Merge request !33 created: http://merge.request.url', (string) $event);
    }

    /**
     * @covers \dogit\DrupalOrg\IssueGraph\Events\StatusChangeEvent
     */
    public function testStatusChangeEvent(): void
    {
        $comment = DrupalOrgComment::fromStub((object) ['id' => 1]);
        $from = 'Active ';
        $to = '» Needs review';
        $event = new StatusChangeEvent($comment, $from, $to);

        $this->assertEquals('Active', $event->from());
        $this->assertEquals('Needs review', $event->to());
        $this->assertEquals('Status change from Active to Needs review', (string) $event);
    }

    /**
     * @covers \dogit\DrupalOrg\IssueGraph\Events\TestResultEvent
     */
    public function testTestResultEvent(): void
    {
        $comment = DrupalOrgComment::fromStub((object) ['id' => 1]);
        $event = new TestResultEvent($comment, '8.0.x', 'PHP 5.5 & MySQL 5.5 14,068 pass, 7 fail');
        $this->assertEquals('8.0.x', $event->version());
        $this->assertEquals('PHP 5.5 & MySQL 5.5 14,068 pass, 7 fail', $event->result());
        $this->assertEquals('🧪 Test result: 8.0.x: ❌ PHP 5.5 & MySQL 5.5 14,068 pass, 7 fail', (string) $event);

        // With no failures:
        $event = new TestResultEvent($comment, '8.0.x', 'PHP 5.5 & MySQL 5.5 14,068 pass');
        $this->assertEquals('🧪 Test result: 8.0.x: ✅ PHP 5.5 & MySQL 5.5 14,068 pass', (string) $event);
    }

    /**
     * @covers \dogit\DrupalOrg\IssueGraph\Events\VersionChangeEvent
     */
    public function testVersionChangeEvent(): void
    {
        $comment = DrupalOrgComment::fromStub((object) ['id' => 1]);
        $from = '8.0.x-dev ';
        $to = '» 8.1.x-dev';
        $event = new VersionChangeEvent($comment, $from, $to);

        $this->assertEquals('8.0.x', $event->from());
        $this->assertEquals('8.1.x', $event->to());
        $this->assertEquals('Version changed from 8.0.x to 8.1.x', (string) $event);
    }
}
