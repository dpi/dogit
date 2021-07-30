<?php

declare(strict_types=1);

namespace dogit\DrupalOrg;

use dogit\DrupalOrg\Objects\DrupalOrgComment;
use dogit\DrupalOrg\Objects\DrupalOrgFile;
use dogit\DrupalOrg\Objects\DrupalOrgIssue;
use dogit\DrupalOrg\Objects\DrupalOrgPatch;
use Http\Client\HttpAsyncClient;
use Http\Promise\Promise;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * Synchronous methods automatically use object repository.
 */
final class DrupalApi implements DrupalApiInterface
{
    private const ENDPOINT_NODE = 'https://www.drupal.org/api-d7/node/%d.json';
    private const ENDPOINT_COMMENT = 'https://www.drupal.org/api-d7/comment/%d.json';
    private const ENDPOINT_FILE = 'https://www.drupal.org/api-d7/file/%d.json';

  protected DrupalOrgObjectRepository $repository;

  protected HttpAsyncClient $httpClient;

  protected RequestFactoryInterface $httpFactory;

  public function __construct(
        RequestFactoryInterface $httpFactory,
        HttpAsyncClient $httpClient,
        DrupalOrgObjectRepository $repository
    ) {
    $this->httpFactory = $httpFactory;
    $this->httpClient = $httpClient;
    $this->repository = $repository;
  }

    public function getIssue(int $nid): DrupalOrgIssue
    {
        $response = $this->httpClient->sendAsyncRequest(
            $this->httpFactory->createRequest('GET', sprintf(static::ENDPOINT_NODE, $nid))
        )->wait();
        $issue = DrupalOrgIssue::fromResponse($response, $this->repository);

        return $this->repository->share($issue);
    }

    public function getCommentAsync(DrupalOrgComment $comment): Promise
    {
        return $this->httpClient->sendAsyncRequest(
            $this->httpFactory->createRequest('GET', sprintf(static::ENDPOINT_COMMENT, $comment->id()))
        );
    }

    public function getFileAsync(DrupalOrgFile $file): Promise
    {
        return $this->httpClient->sendAsyncRequest(
            $this->httpFactory->createRequest('GET', sprintf(static::ENDPOINT_FILE, $file->id()))
        );
    }

    public function getPatchFileAsync(DrupalOrgPatch $patch): Promise
    {
        return $this->httpClient->sendAsyncRequest(
            $this->httpFactory->createRequest('GET', $patch->getUrl())
        );
    }
}
