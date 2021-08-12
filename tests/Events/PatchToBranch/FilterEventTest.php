<?php

declare(strict_types=1);

namespace dogit\tests\Events\PatchToBranch;

use dogit\Commands\Options\PatchToBranchOptions;
use dogit\DrupalOrg\IssueGraph\Events\IssueEvent;
use dogit\DrupalOrg\Objects\DrupalOrgComment;
use dogit\DrupalOrg\Objects\DrupalOrgPatch;
use dogit\Events\PatchToBranch\FilterEvent;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \dogit\Events\PatchToBranch\FilterEvent
 */
final class FilterEventTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @covers \dogit\Events\PatchToBranch\FilterEvent
     */
    public function testEvent(): void
    {
        $patches = [new DrupalOrgPatch(123)];
        $comment = DrupalOrgComment::fromStub((object) ['id' => 1]);
        $issueEvents = [IssueEvent::fromRaw($comment, 'Foo', ['abc' => 123])];
        $logger = $this->createMock(LoggerInterface::class);
        $options = new PatchToBranchOptions();

        $event = new FilterEvent($patches, $issueEvents, $logger, $options);
        $this->assertEquals($patches, $event->patches);
        $this->assertEquals($issueEvents, $event->issueEvents);
        $this->assertEquals($logger, $event->logger);
        $this->assertEquals($options, $event->options);
    }

    /**
     * @covers ::setFailure
     * @covers ::isPropagationStopped
     */
    public function testIsPropagationStopped(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $options = new PatchToBranchOptions();

        $event = new FilterEvent([], [], $logger, $options);
        $this->assertFalse($event->isPropagationStopped());
        $event->setFailure();
        $this->assertTrue($event->isPropagationStopped());
    }
}
