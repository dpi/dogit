<?php

declare(strict_types=1);

namespace dogit\tests\DrupalOrg;

use dogit\DrupalOrg\DrupalApi;
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
use Psr\Http\Message\RequestFactoryInterface;

/**
 * @coversDefaultClass \dogit\DrupalOrg\DrupalApi
 */
final class DrupalOrgApiTest extends TestCase
{
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
     * @covers ::getIssue
     */
    public function testGetIssue(): void
    {
        $api = new DrupalApi($this->httpFactory, $this->httpAsyncClient, $this->repository);

        $issue = $api->getIssue(2350939);
        $this->assertFalse($issue->isStub());
        $this->assertEquals(2350939, $issue->id());
    }

    /**
     * @covers ::getCommentAsync
     */
    public function testGetCommentAsync(): void
    {
        $api = new DrupalApi($this->httpFactory, $this->httpAsyncClient, $this->repository);

        $comment = DrupalOrgComment::fromStub((object) ['id' => 13370001]);
        $comment->setRepository($this->repository);
        $promise = $api->getCommentAsync($comment);

        $this->assertTrue($comment->isStub());
        $response = $promise->wait();
        $comment->importResponse($response);
        $this->assertFalse($comment->isStub());
        $this->assertEquals(new \DateTimeImmutable('2014-05-13 19:40:00'), $comment->getCreated());
    }

    /**
     * @covers ::getFileAsync
     */
    public function testGetFileAsync(): void
    {
        $api = new DrupalApi($this->httpFactory, $this->httpAsyncClient, $this->repository);

        $file = new DrupalOrgFile(22220001);
        $file->setRepository($this->repository);
        $promise = $api->getFileAsync($file);

        $this->assertTrue($file->isStub());
        $response = $promise->wait();
        $file->importResponse($response);
        $this->assertFalse($file->isStub());
        $this->assertEquals(new \DateTimeImmutable('2015-04-10 22:25:03'), $file->getCreated());
    }

    /**
     * @covers ::getPatchFileAsync
     */
    public function testGetPatchFileAsync(): void
    {
        $api = new DrupalApi($this->httpFactory, $this->httpAsyncClient, $this->repository);

        $file = DrupalOrgFile::fromResponse(
            new Response(200, [], TestUtilities::getFixture('file-22220001.json')),
            $this->repository,
        );

        $patch = DrupalOrgPatch::fromFile($file);
        $promise = $api->getPatchFileAsync($patch);
        $response = $promise->wait();
        $this->assertEquals('This is a sample patch file.', (string) $response->getBody());
    }
}
