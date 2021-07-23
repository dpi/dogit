<?php

declare(strict_types=1);

namespace dogit;

use Http\Client\HttpAsyncClient;
use Psr\Http\Message\RequestFactoryInterface;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\Response;

final class HttplugBrowser extends AbstractBrowser
{
    protected HttpAsyncClient $httpClient;
    protected RequestFactoryInterface $httpFactory;

    public function __construct(RequestFactoryInterface $httpFactory, HttpAsyncClient $httpClient)
    {
        parent::__construct();
        $this->httpFactory = $httpFactory;
        $this->httpClient = $httpClient;
    }

    /**
     * @param \Psr\Http\Message\RequestInterface|\Symfony\Component\BrowserKit\Request $request
     */
    protected function doRequest($request): Response
    {
        $response = $this->httpClient->sendAsyncRequest(
            $this->httpFactory->createRequest($request->getMethod(), $request->getUri())
        )->wait();

        return new Response(
            (string) $response->getBody(),
            $response->getStatusCode(),
            $response->getHeaders(),
        );
    }
}
