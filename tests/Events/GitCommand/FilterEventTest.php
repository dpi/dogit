<?php

declare(strict_types=1);

namespace dogit\tests\Events\GitCommand;

use dogit\Commands\Options\GitCommandOptions;
use dogit\DrupalOrg\IssueGraph\Events\IssueEvent;
use dogit\DrupalOrg\Objects\DrupalOrgComment;
use dogit\DrupalOrg\Objects\DrupalOrgPatch;
use dogit\Events\GitCommand\FilterEvent;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \dogit\Events\GitCommand\FilterEvent
 */
final class FilterEventTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @covers \dogit\Events\GitCommand\FilterEvent
     */
    public function testEvent(): void
    {
        $patches = [new DrupalOrgPatch(123)];
        $comment = DrupalOrgComment::fromStub((object) ['id' => 1]);
        $issueEvents = [IssueEvent::fromRaw($comment, 'Foo', ['abc' => 123])];
        $logger = $this->createMock(LoggerInterface::class);
        $options = new GitCommandOptions();

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
        $options = new GitCommandOptions();

        $event = new FilterEvent([], [], $logger, $options);
        $this->assertFalse($event->isPropagationStopped());
        $event->setFailure();
        $this->assertTrue($event->isPropagationStopped());
    }
}
