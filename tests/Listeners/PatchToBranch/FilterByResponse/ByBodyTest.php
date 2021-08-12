<?php

declare(strict_types=1);

namespace dogit\tests\Listeners\PatchToBranch\Filter;

use dogit\DrupalOrg\Objects\DrupalOrgComment;
use dogit\DrupalOrg\Objects\DrupalOrgPatch;
use dogit\Events\PatchToBranch\FilterByResponseEvent;
use dogit\Listeners\PatchToBranch\FilterByResponse\ByBody;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \dogit\Listeners\PatchToBranch\FilterByResponse\ByBody
 */
final class ByBodyTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @covers ::__invoke
     */
    public function testFilter(): void
    {
        $comment1 = DrupalOrgComment::fromStub((object) ['id' => 101])
            ->setSequence(1);
        $comment2 = DrupalOrgComment::fromStub((object) ['id' => 102])
            ->setSequence(2);

        $patches = [];
        $patches[] = (new DrupalOrgPatch(201))
            ->setParent($comment1)
            ->setContents('patch 1 contents')
            ->importResponse(new Response(200, [], (string) \json_encode([
                'url' => 'http://example.com/patch1.patch',
            ])));
        $patches[] = (new DrupalOrgPatch(202))
            ->setParent($comment2)
            // Intentionally empty:
            ->setContents('')
            ->importResponse(new Response(200, [], (string) \json_encode([
                'url' => 'http://example.com/patch2.patch',
            ])));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(1))
            ->method('debug')
            ->with('Removed empty patch #{comment_id} {patch_url}.', [
                'comment_id' => 2,
                'patch_url' => 'http://example.com/patch2.patch',
            ]);

        $event = new FilterByResponseEvent($patches, $logger);
        $filter = new ByBody();

        $this->assertCount(2, $event->patches);
        $filter($event);
        $this->assertCount(1, $event->patches);
    }

    /**
     * @covers ::__invoke
     */
    public function testFilterMissingPatchContents(): void
    {
        $comment1 = DrupalOrgComment::fromStub((object) ['id' => 101])
            ->setSequence(1);

        $patches = [];
        $patches[] = (new DrupalOrgPatch(201))
            ->setParent($comment1)
            ->importResponse(new Response(200, [], (string) \json_encode([
                'url' => 'http://example.com/patch1.patch',
            ])));

        $logger = $this->createMock(LoggerInterface::class);

        $event = new FilterByResponseEvent($patches, $logger);
        $filter = new ByBody();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Missing patch contents for patch #1 http://example.com/patch1.patch');
        $filter($event);
    }
}
