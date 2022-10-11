<?php

declare(strict_types=1);

namespace dogit\tests\DrupalOrg\IssueGraph;

use dogit\DrupalOrg\DrupalApi;
use dogit\DrupalOrg\DrupalOrgObjectIterator;
use dogit\DrupalOrg\DrupalOrgObjectRepository;
use dogit\DrupalOrg\IssueGraph\DrupalOrgIssueGraph;
use dogit\DrupalOrg\IssueGraph\Events\CommentEvent;
use dogit\DrupalOrg\IssueGraph\Events\MergeRequestCreateEvent;
use dogit\DrupalOrg\IssueGraph\Events\StatusChangeEvent;
use dogit\DrupalOrg\IssueGraph\Events\TestResultEvent;
use dogit\DrupalOrg\IssueGraph\Events\VersionChangeEvent;
use dogit\tests\DogitGuzzleTestMiddleware;
use dogit\Utility;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use Http\Adapter\Guzzle7\Client;
use Http\Discovery\Psr17FactoryDiscovery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \dogit\DrupalOrg\IssueGraph\DrupalOrgIssueGraph
 */
final class DrupalOrgIssueGraphTest extends TestCase
{
    /**
     * @covers ::findComments
     * @covers ::findMergeRequestBranches
     * @covers ::graph
     */
    public function testGraph(): void
    {
        $httpFactory = Psr17FactoryDiscovery::findRequestFactory();
        $handlerStack = HandlerStack::create();
        $handlerStack->push(new DogitGuzzleTestMiddleware(), 'test_middleware');
        $httpAsyncClient = new Client(new GuzzleClient([
            'handler' => $handlerStack,
        ]));
        $repository = new DrupalOrgObjectRepository();
        $url = 'https://www.drupal.org/project/drupal/issues/2350939';

        $events = iterator_to_array((new DrupalOrgIssueGraph(
            $httpFactory,
            $httpAsyncClient,
            $repository,
            $url,
        ))->graph());

        $this->assertCount(19, $events);
        $this->assertInstanceOf(CommentEvent::class, $events[0]);
        $this->assertInstanceOf(StatusChangeEvent::class, $events[1]);
        $this->assertInstanceOf(TestResultEvent::class, $events[2]);
        $this->assertInstanceOf(CommentEvent::class, $events[3]);
        $this->assertInstanceOf(VersionChangeEvent::class, $events[4]);
        $this->assertInstanceOf(CommentEvent::class, $events[5]);
        $this->assertInstanceOf(CommentEvent::class, $events[6]);
        $this->assertInstanceOf(CommentEvent::class, $events[7]);
        $this->assertInstanceOf(TestResultEvent::class, $events[8]);
        $this->assertInstanceOf(CommentEvent::class, $events[9]);
        $this->assertInstanceOf(VersionChangeEvent::class, $events[10]);
        $this->assertInstanceOf(CommentEvent::class, $events[11]);
        $this->assertInstanceOf(TestResultEvent::class, $events[12]);
        $this->assertInstanceOf(CommentEvent::class, $events[13]);
        $this->assertInstanceOf(VersionChangeEvent::class, $events[14]);
        $this->assertInstanceOf(CommentEvent::class, $events[15]);
        $this->assertInstanceOf(MergeRequestCreateEvent::class, $events[16]);
        $this->assertInstanceOf(CommentEvent::class, $events[17]);
        $this->assertInstanceOf(MergeRequestCreateEvent::class, $events[18]);

        // Unstub comments so events can stringify.
        $api = new DrupalApi($httpFactory, $httpAsyncClient, $repository);
        $objectIterator = new DrupalOrgObjectIterator($api, $this->createMock(LoggerInterface::class));
        $objectIterator->unstubComments(iterator_to_array(Utility::getCommentsFromEvents($events)));

        $this->assertEquals('🗣 Comment by 👤 larowlan on Tue, 13 May 2014 19:40:00 +0000', (string) $events[0]);
        $this->assertEquals('Status change from Needs work to Needs review', (string) $events[1]);
        $this->assertEquals('🧪 Test result: 8.2.x: ✅ PHP 7.1 & MySQL 5.7 26,400 pass', (string) $events[2]);
        $this->assertEquals('🗣 Comment by 👤 larowlan on Tue, 13 May 2014 22:26:40 +0000', (string) $events[3]);
        $this->assertEquals('Version changed from 8.2.x to 8.3.x', (string) $events[4]);
        $this->assertEquals('🗣 Comment by 👤 larowlan on Wed, 14 May 2014 01:13:20 +0000', (string) $events[5]);
        $this->assertEquals('🗣 Comment by 👤 larowlan on Wed, 14 May 2014 04:00:00 +0000', (string) $events[6]);
        $this->assertEquals('🗣 Comment by 👤 larowlan on Wed, 14 May 2014 06:46:40 +0000', (string) $events[7]);
        $this->assertEquals('🧪 Test result: 8.3.x: ✅ PHP 7.1 & MySQL 5.7 26,400 pass', (string) $events[8]);
        $this->assertEquals('🗣 Comment by 👤 larowlan on Wed, 14 May 2014 06:46:40 +0000', (string) $events[9]);
        $this->assertEquals('Version changed from 8.3.x to 8.4.x', (string) $events[10]);
        $this->assertEquals('🗣 Comment by 👤 larowlan on Wed, 14 May 2014 06:46:40 +0000', (string) $events[11]);
        $this->assertEquals('🧪 Test result: 8.4.x: ✅ PHP 7.1 & MySQL 5.7 26,400 pass', (string) $events[12]);
        $this->assertEquals('🗣 Comment by 👤 larowlan on Wed, 14 May 2014 15:06:40 +0000', (string) $events[13]);
        $this->assertEquals('Version changed from 8.4.x to 8.5.x', (string) $events[14]);
        $this->assertEquals('🗣 Comment by 🤖 System Message on Wed, 14 May 2014 15:06:40 +0000', (string) $events[15]);
        $this->assertEquals('Merge request !333 created: https://git.drupalcode.org/project/drupal/-/merge_requests/333', (string) $events[16]);
    }

    /**
     * @covers ::graph
     */
    public function testWithoutMergeRequests(): void
    {
        $httpFactory = Psr17FactoryDiscovery::findRequestFactory();
        $handlerStack = HandlerStack::create();
        $handlerStack->push(new DogitGuzzleTestMiddleware(), 'test_middleware');
        $httpAsyncClient = new Client(new GuzzleClient([
            'handler' => $handlerStack,
        ]));
        $repository = new DrupalOrgObjectRepository();
        $url = 'https://www.drupal.org/project/drupal/issues/11110001';

        $events = iterator_to_array((new DrupalOrgIssueGraph(
            $httpFactory,
            $httpAsyncClient,
            $repository,
            $url,
        ))->graph());

        $this->assertCount(15, $events);
        $this->assertInstanceOf(CommentEvent::class, $events[0]);
        $this->assertInstanceOf(StatusChangeEvent::class, $events[1]);
        $this->assertInstanceOf(TestResultEvent::class, $events[2]);
        $this->assertInstanceOf(CommentEvent::class, $events[3]);
        $this->assertInstanceOf(VersionChangeEvent::class, $events[4]);
        $this->assertInstanceOf(CommentEvent::class, $events[5]);
        $this->assertInstanceOf(CommentEvent::class, $events[6]);
        $this->assertInstanceOf(CommentEvent::class, $events[7]);
        $this->assertInstanceOf(TestResultEvent::class, $events[8]);
        $this->assertInstanceOf(CommentEvent::class, $events[9]);
        $this->assertInstanceOf(VersionChangeEvent::class, $events[10]);
        $this->assertInstanceOf(CommentEvent::class, $events[11]);
        $this->assertInstanceOf(TestResultEvent::class, $events[12]);
        $this->assertInstanceOf(CommentEvent::class, $events[13]);
        $this->assertInstanceOf(VersionChangeEvent::class, $events[14]);
    }
}
