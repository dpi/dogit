<?php

declare(strict_types=1);

namespace dogit\tests;

use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * Captures requests to Drupal.org API.
 *
 * If a scenario isnt handled an exception is thrown, a real request
 * is never sent to the real endpoint.
 */
final class DogitGuzzleTestMiddleware
{
    public function __invoke(callable $nextHandler): callable
    {
        return function (RequestInterface $request, array $options): PromiseInterface {
            $path = $request->getUri()->getPath();

            if (str_contains($path, '/api-d7/node/11110003')) {
                return new FulfilledPromise(
                    new Response(200, [], TestUtilities::getFixture('issue-11110003.json')),
                );
            }
            if (str_contains($path, '/api-d7/node/11110002')) {
                return new FulfilledPromise(
                    new Response(200, [], TestUtilities::getFixture('issue-11110002.json')),
                );
            } elseif (str_contains($path, '/api-d7/node/')) {
                return new FulfilledPromise(
                    new Response(200, [], TestUtilities::getFixture('issue.json')),
                );
            }

            if (str_contains($path, '/api-d7/comment/')) {
                preg_match('/\/api-d7\/comment\/(?<cid>\d+)\.json/', $path, $matches);
                ['cid' => $cid] = $matches;

                return new FulfilledPromise(
                    new Response(200, [], TestUtilities::getFixture(sprintf('comment-%s.json', $cid))),
                );
            }

            if (str_contains($path, '/api-d7/file/')) {
                preg_match('/\/api-d7\/file\/(?<fid>\d+)\.json/', $path, $matches);
                ['fid' => $fid] = $matches;

                return new FulfilledPromise(
                    new Response(200, [], TestUtilities::getFixture(sprintf('file-%s.json', $fid))),
                );
            }

            if (str_contains($path, '/project/drupal/issues/11110001')) {
                return new FulfilledPromise(
                    new Response(200, [], TestUtilities::getFixture('issue-11110001.html')),
                );
            }
            if (str_contains($path, '/project/drupal/issues/11110003')) {
                return new FulfilledPromise(
                    new Response(200, [], TestUtilities::getFixture('issue-11110003.html')),
                );
            } elseif (str_contains($path, '/project/drupal/issues/11110002')) {
                return new FulfilledPromise(
                    new Response(200, [], TestUtilities::getFixture('issue-11110002.html')),
                );
            } elseif (str_contains($path, '/project/drupal/issues/')) {
                return new FulfilledPromise(
                    new Response(200, [], TestUtilities::getFixture('issue.html')),
                );
            }

            if (str_contains($path, '/files/issues/')) {
                return new FulfilledPromise(
                    new Response(200, [], TestUtilities::getFixture('test.patch')),
                );
            }

            throw new \Exception('Unhandled scenario.');
        };
    }
}
