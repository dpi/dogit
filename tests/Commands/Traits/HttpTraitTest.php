<?php

declare(strict_types=1);

namespace dogit\tests\Commands\Traits;

use dogit\Commands\Traits\HttpTrait;
use dogit\tests\DogitTestBase;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \dogit\Commands\Traits\HttpTrait
 */
final class HttpTraitTest extends DogitTestBase
{
    public function testLogs(): void
    {
        $command = new class {
            use HttpTrait {
                http as originalHttp;
            }

            /**
             * @param string[] $cookies
             *
             * @return array{0:\Psr\Http\Message\RequestFactoryInterface, 1:\Http\Client\HttpClient&\Http\Client\HttpAsyncClient}
             */
            public function http(LoggerInterface $logger, bool $noHttpCache = false, array $cookies = [])
            {
                return $this->originalHttp($logger, $noHttpCache, $cookies);
            }
        };

        $logger = \Mockery::mock(LoggerInterface::class);
        $logger->expects('log')
            ->with('debug', 'GET 200 <href=http://example.com/foo>http://example.com/foo</> [Cache MISS]');
        $logger->expects('error')
            ->never();

        [$httpFactory, $httpAsyncClient] = $command->http($logger);

        $command->handlerStack()->push(function (callable $handler): callable {
            return static function ($request, array $options) {
                return new FulfilledPromise(
                    new Response(200, [
                        'Content-Type' => 'text/plain',
                    ], 'All requests are caught for tests'),
                );
            };
        }, 'request_killer');

        $request = $httpFactory->createRequest('GET', 'http://example.com/foo');
        $promise = $httpAsyncClient->sendAsyncRequest($request);
        $response = $promise->wait();
        $this->assertEquals('All requests are caught for tests', (string) $response->getBody());
    }
}
