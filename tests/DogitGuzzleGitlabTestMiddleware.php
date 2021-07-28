<?php

declare(strict_types=1);

namespace dogit\tests;

use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * Captures requests to the Drupal Gitlab instances.
 *
 * If a scenario isnt handled an exception is thrown, a real request
 * is never sent to the real endpoint.
 */
final class DogitGuzzleGitlabTestMiddleware
{
    public function __invoke(callable $nextHandler): callable
    {
        return function (RequestInterface $request, array $options): PromiseInterface {
            $host = $request->getHeader('Host')[0] ?? null;
            if ('git.drupalcode.org' !== $host) {
                throw new InvalidArgumentException('Unexpected request host: ' . $host);
            }

            $path = $request->getUri()->getPath();

            if ('/api/v4/projects/project%2Ffoo_bar_baz' === $path) {
                return new FulfilledPromise(
                    new Response(200, [
                        'Content-Type' => 'application/json',
                    ], TestUtilities::getFixture('gitlab/project-foo_bar_baz.json')),
                );
            }

            if ('/api/v4/projects/13371337/merge_requests' === $path) {
                return new FulfilledPromise(
                    new Response(200, [
                        'Content-Type' => 'application/json',
                    ], TestUtilities::getFixture('gitlab/merge_requests-foo_bar_baz.json')),
                );
            }

            if ('/api/v4/projects/73253' === $path) {
                return new FulfilledPromise(
                    new Response(200, [
                        'Content-Type' => 'application/json',
                    ], TestUtilities::getFixture('gitlab/project-mr.json')),
                );
            }
            if ('/api/v4/projects/project%2Fnomrs' === $path) {
                return new FulfilledPromise(
                    new Response(200, [
                        'Content-Type' => 'application/json',
                    ], TestUtilities::getFixture('gitlab/project-nomrs.json')),
                );
            }

            if ('/api/v4/projects/13371338/merge_requests' === $path) {
                return new FulfilledPromise(
                    new Response(200, [
                        'Content-Type' => 'application/json',
                    ], TestUtilities::getFixture('gitlab/merge_requests-nomrs.json')),
                );
            }

            throw new InvalidArgumentException('Unhandled scenario.');
        };
    }
}
