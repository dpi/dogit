<?php

declare(strict_types=1);

namespace dogit\tests\Events\PatchToBranch;

use dogit\DrupalOrg\Objects\DrupalOrgPatch;
use dogit\Events\PatchToBranch\FilterByResponseEvent;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \dogit\Events\PatchToBranch\FilterByResponseEvent
 */
final class FilterByResponseEventTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @covers \dogit\Events\PatchToBranch\FilterByResponseEvent
     */
    public function testEvent(): void
    {
        $patches = [new DrupalOrgPatch(123)];
        $logger = $this->createMock(LoggerInterface::class);

        $event = new FilterByResponseEvent($patches, $logger);
        $this->assertEquals($patches, $event->patches);
        $this->assertEquals($logger, $event->logger);
    }

    /**
     * @covers ::setFailure
     * @covers ::isPropagationStopped
     */
    public function testIsPropagationStopped(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $event = new FilterByResponseEvent([], $logger);
        $this->assertFalse($event->isPropagationStopped());
        $event->setFailure();
        $this->assertTrue($event->isPropagationStopped());
    }
}
