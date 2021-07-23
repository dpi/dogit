<?php

declare(strict_types=1);

namespace dogit\Commands\Traits;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Http\Adapter\Guzzle7\Client;
use Http\Discovery\Psr17FactoryDiscovery;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\Flysystem2Storage;
use Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

trait HttpTrait
{
    private ?HandlerStack $handlerStack;

    /**
     * @param string[] $cookies
     *
     * @return array{0:\Psr\Http\Message\RequestFactoryInterface, 1:\Http\Client\HttpAsyncClient}
     */
    protected function http(LoggerInterface $logger, bool $noHttpCache = false, array $cookies = [])
    {
        $this->handlerStack()->push(Middleware::log(
            $logger,
            new MessageFormatter('{method} {code} <href={uri}>{uri}</>'),
            LogLevel::DEBUG,
        ));

        if (!$noHttpCache) {
            $dir = '/tmp/dogit/http_cache/';
            $flySystem = new Flysystem2Storage(new LocalFilesystemAdapter($dir));
            $cacheStrategy = new PrivateCacheStrategy($flySystem);
            $this->handlerStack()->push(new CacheMiddleware($cacheStrategy), 'cache');
        }

        $cookieArray = array_map(fn (string $cookie): SetCookie => SetCookie::fromString($cookie), $cookies);
        $cookieJar = new CookieJar(false, $cookieArray);

        $httpFactory = Psr17FactoryDiscovery::findRequestFactory();
        $httpAsyncClient = new Client(new GuzzleClient([
            'handler' => $this->handlerStack(),
            'cookies' => $cookieJar,
            'headers' => [
                'User-Agent' => 'Dogit www.dogit.dev',
            ],
        ]));

        return [$httpFactory, $httpAsyncClient];
    }

    public function handlerStack(): HandlerStack
    {
        return $this->handlerStack = ($this->handlerStack ?? HandlerStack::create());
    }
}
