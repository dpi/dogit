<?php

declare(strict_types=1);

namespace dogit\tests\Events\PatchToBranch;

use dogit\DrupalOrg\DrupalOrgObjectIterator;
use dogit\DrupalOrg\IssueGraph\Events\IssueEvent;
use dogit\DrupalOrg\Objects\DrupalOrgComment;
use dogit\DrupalOrg\Objects\DrupalOrgPatch;
use dogit\Events\PatchToBranch\VersionEvent;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \dogit\Events\PatchToBranch\VersionEvent
 */
final class VersionEventTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @covers \dogit\Events\PatchToBranch\VersionEvent
     */
    public function testEvent(): void
    {
        $patches = [new DrupalOrgPatch(123)];
        $comment = DrupalOrgComment::fromStub((object) ['id' => 1]);
        $issueEvents = [IssueEvent::fromRaw($comment, 'Foo', ['abc' => 123])];
        $objectIterator = $this->createMock(DrupalOrgObjectIterator::class);
        $logger = $this->createMock(LoggerInterface::class);

        $event = new VersionEvent($patches, $issueEvents, $objectIterator, $logger);
        $this->assertEquals($patches, $event->patches);
        $this->assertEquals($issueEvents, $event->issueEvents);
        $this->assertEquals($objectIterator, $event->objectIterator);
        $this->assertEquals($logger, $event->logger);
    }

    /**
     * @covers ::setFailure
     * @covers ::isPropagationStopped
     */
    public function testIsPropagationStopped(): void
    {
        $objectIterator = $this->createMock(DrupalOrgObjectIterator::class);
        $logger = $this->createMock(LoggerInterface::class);

        $event = new VersionEvent([], [], $objectIterator, $logger);
        $this->assertFalse($event->isPropagationStopped());
        $event->setFailure();
        $this->assertTrue($event->isPropagationStopped());
    }
}
