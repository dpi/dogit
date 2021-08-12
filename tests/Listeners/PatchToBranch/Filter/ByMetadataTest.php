<?php

declare(strict_types=1);

namespace dogit\tests\Listeners\PatchToBranch\Filter;

use dogit\Commands\Options\PatchToBranchOptions;
use dogit\DrupalOrg\IssueGraph\Events\IssueEvent;
use dogit\DrupalOrg\IssueGraph\Events\TestResultEvent;
use dogit\DrupalOrg\Objects\DrupalOrgComment;
use dogit\DrupalOrg\Objects\DrupalOrgPatch;
use dogit\Events\PatchToBranch\FilterEvent;
use dogit\Listeners\PatchToBranch\Filter\ByMetadata;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \dogit\Listeners\PatchToBranch\Filter\ByMetadata
 */
final class ByMetadataTest extends TestCase
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
        $comment3 = DrupalOrgComment::fromStub((object) ['id' => 103])
            ->setSequence(3);
        $comment4 = DrupalOrgComment::fromStub((object) ['id' => 104])
            ->setSequence(4);

        $patches = [];
        $patches[] = (new DrupalOrgPatch(201))
            ->setParent($comment1)
            ->importResponse(new Response(200, [], (string) \json_encode([
                'url' => 'http://example.com/patch1.patch',
            ])));
        $patches[] = (new DrupalOrgPatch(202))
            ->setParent($comment2)
            ->importResponse(new Response(200, [], (string) \json_encode([
                'url' => 'http://example.com/patch2-interdiff.txt',
            ])));
        $patches[] = (new DrupalOrgPatch(203))
            ->setParent($comment3)
            ->importResponse(new Response(200, [], (string) \json_encode([
                'url' => 'http://example.com/patch3.patch',
            ])));
        $patches[] = (new DrupalOrgPatch(204))
            ->setParent($comment4)
            ->importResponse(new Response(200, [], (string) \json_encode([
                'url' => 'http://example.com/test-only-patch4.patch',
            ])));

        $issueEvents = [];
        $issueEvents[] = IssueEvent::fromRaw($comment1, 'Foo', ['abc' => 123]);
        $issueEvents[] = new TestResultEvent($comment2, '1.0.x', 'PHP 7.1 & MySQL 5.7 26,400 pass');
        $issueEvents[] = new TestResultEvent($comment3, '1.1.x', 'Unable to apply patch blah-blah.patch. Unable to apply patch. See the log in the details link for more information.');
        $issueEvents[] = new TestResultEvent($comment4, '1.2.x', 'PHP 5.5 & MySQL 5.5 14,068 pass, 7 fail');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(3))
            ->method('debug')
            ->withConsecutive(
                ['Comment #{comment_id}: {patch_url} looks like an interdiff', [
                    'comment_id' => 2,
                    'patch_url' => 'http://example.com/patch2-interdiff.txt',
                ]],
                ['Comment #{comment_id}: {patch_url} failed to apply during test run.', [
                    'comment_id' => 3,
                    'patch_url' => 'http://example.com/patch3.patch',
                ]],
                ['Comment #{comment_id}: {patch_url} looks like a test only patch', [
                    'comment_id' => 4,
                    'patch_url' => 'http://example.com/test-only-patch4.patch',
                ]],
            );

        $options = new PatchToBranchOptions();

        $event = new FilterEvent($patches, $issueEvents, $logger, $options);
        $filter = new ByMetadata();

        $this->assertCount(4, $event->patches);
        $filter($event);
        $this->assertCount(1, $event->patches);
    }
}
