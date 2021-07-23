<?php

declare(strict_types=1);

namespace dogit\tests\DrupalOrg;

use dogit\DrupalOrg\DrupalApi;
use dogit\DrupalOrg\DrupalOrgObjectIterator;
use dogit\DrupalOrg\DrupalOrgObjectRepository;
use dogit\DrupalOrg\Objects\DrupalOrgComment;
use dogit\DrupalOrg\Objects\DrupalOrgFile;
use dogit\DrupalOrg\Objects\DrupalOrgPatch;
use dogit\tests\DogitGuzzleTestMiddleware;
use dogit\tests\TestUtilities;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Http\Adapter\Guzzle7\Client;
use Http\Discovery\Psr17FactoryDiscovery;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \dogit\DrupalOrg\DrupalOrgObjectIterator
 */
class DrupalOrgObjectIteratorTest extends TestCase
{
    use ProphecyTrait;

    private RequestFactoryInterface $httpFactory;
    private Client $httpAsyncClient;
    private DrupalOrgObjectRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpFactory = Psr17FactoryDiscovery::findRequestFactory();
        $handlerStack = HandlerStack::create();
        $handlerStack->push(new DogitGuzzleTestMiddleware(), 'test_middleware');
        $this->httpAsyncClient = new Client(new GuzzleClient([
            'handler' => $handlerStack,
        ]));
        $this->repository = new DrupalOrgObjectRepository();
    }

    /**
     * @covers ::unstubComments
     * @covers ::filterStubbed
     * @covers ::unwrap
     */
    public function testUnstubComments(): void
    {
        $api = new DrupalApi($this->httpFactory, $this->httpAsyncClient, $this->repository);

        $objectIterator = new DrupalOrgObjectIterator($api, $this->createMock(LoggerInterface::class));

        $comments = [];
        $comments[] = DrupalOrgComment::fromStub((object) ['id' => 13370001])->setRepository($this->repository);
        $comments[] = DrupalOrgComment::fromStub((object) ['id' => 13370002])->setRepository($this->repository);
        // Duplicate ID's are filtered out.
        $comments[] = DrupalOrgComment::fromStub((object) ['id' => 13370001])->setRepository($this->repository);
        // Test even if an object is not a stub, it must be not filtered out.
        $nonStubComment = $this->createMock(DrupalOrgComment::class);
        $nonStubComment->method('id')->willReturn(13370003);
        $nonStubComment->method('isStub')->willReturn(false);
        $comments[] = $nonStubComment;

        $result = $objectIterator->unstubComments($comments);
        $this->assertCount(3, $result);
        $this->assertEquals(13370001, $result[0]->id());
        $this->assertEquals(13370002, $result[1]->id());
        $this->assertEquals(13370003, $result[3]->id());
    }

    /**
     * @covers ::unstubFiles
     * @covers ::filterStubbed
     * @covers ::unwrap
     */
    public function testUnstubFiles(): void
    {
        $api = new DrupalApi($this->httpFactory, $this->httpAsyncClient, $this->repository);

        $objectIterator = new DrupalOrgObjectIterator($api, $this->createMock(LoggerInterface::class));

        $files = [];
        $files[] = DrupalOrgFile::fromStub((object) ['id' => 22220001])->setRepository($this->repository);
        $files[] = DrupalOrgFile::fromStub((object) ['id' => 22220002])->setRepository($this->repository);
        // Duplicate ID's are filtered out.
        $files[] = DrupalOrgFile::fromStub((object) ['id' => 22220001])->setRepository($this->repository);
        // Test even if an object is not a stub, it must be not filtered out.
        $nonStubComment = $this->createMock(DrupalOrgFile::class);
        $nonStubComment->method('id')->willReturn(22220003);
        $nonStubComment->method('isStub')->willReturn(false);
        $files[] = $nonStubComment;

        $result = $objectIterator->unstubFiles($files);
        $this->assertCount(3, $result);
        $this->assertEquals(22220001, $result[0]->id());
        $this->assertEquals(22220002, $result[1]->id());
        $this->assertEquals(22220003, $result[3]->id());
    }

    /**
     * @covers ::downloadPatchFiles
     * @covers ::unwrap
     */
    public function testDownloadPatchFiles(): void
    {
        $api = new DrupalApi($this->httpFactory, $this->httpAsyncClient, $this->repository);

        $objectIterator = new DrupalOrgObjectIterator($api, $this->createMock(LoggerInterface::class));

        $files = [];
        $files[] = DrupalOrgPatch::fromResponse(
            new Response(200, [], TestUtilities::getFixture('file-22220001.json')),
            $this->repository,
        );
        $files[] = DrupalOrgPatch::fromResponse(
            new Response(200, [], TestUtilities::getFixture('file-22220002.json')),
            $this->repository,
        );
        $files[] = DrupalOrgPatch::fromResponse(
            new Response(200, [], TestUtilities::getFixture('file-22220001.json')),
            $this->repository,
        );

        $result = $objectIterator->downloadPatchFiles($files);
        $this->assertEquals(22220001, $result[0]->id());
        $this->assertEquals('This is a sample patch file.', $result[0]->getContents());
        $this->assertEquals(22220002, $result[1]->id());
        $this->assertEquals('This is a sample patch file.', $result[1]->getContents());
    }
}
