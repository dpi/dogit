<?php

declare(strict_types=1);

namespace dogit\DrupalOrg;

use dogit\DrupalOrg\Objects\DrupalOrgComment;
use dogit\DrupalOrg\Objects\DrupalOrgFile;
use dogit\DrupalOrg\Objects\DrupalOrgObject;
use dogit\DrupalOrg\Objects\DrupalOrgPatch;
use dogit\Utility;
use GuzzleHttp\Promise\EachPromise;
use Http\Promise\Promise;
use Psr\Log\LoggerInterface;

class DrupalOrgObjectIterator
{

  protected LoggerInterface $logger;

  protected DrupalApiInterface $api;

  public function __construct(DrupalApiInterface $api, LoggerInterface $logger)
    {
      $this->api = $api;
      $this->logger = $logger;
    }

    /**
     * @param \dogit\DrupalOrg\Objects\DrupalOrgComment[] $comments
     *
     * @return \dogit\DrupalOrg\Objects\DrupalOrgComment[]
     *   It's not necessary to use return value, can re-use $comments. Though return
     *   value will have its objects de-duplicated.
     */
    public function unstubComments(array $comments): array
    {
        $comments = Utility::deduplicateDrupalOrgObjects($comments);
        $requestComments = $this->filterStubbed($comments);
        if (0 === count($requestComments)) {
            return $comments;
        }

        $promises = array_map(
            fn (DrupalOrgComment $comment): Promise => $this->api->getCommentAsync($comment),
            $requestComments,
        );
        $this->logger->debug('Batched comment requests for {ids}', [
            'ids' => implode(', ', array_map(
                fn (DrupalOrgComment $comment): int => $comment->id(),
                $requestComments
            )),
        ]);

        $responses = $this->unwrap($promises);
        foreach ($responses as $key => $response) {
            $requestComments[$key]->importResponse($response);
        }

        return $comments;
    }

    /**
     * @param \dogit\DrupalOrg\Objects\DrupalOrgFile[] $files
     *
     * @return \dogit\DrupalOrg\Objects\DrupalOrgFile[]
     *   It's not necessary to use return value, can re-use $files. Though return
     *   value will have its objects de-duplicated.
     */
    public function unstubFiles(array $files): array
    {
        $files = Utility::deduplicateDrupalOrgObjects($files);
        $requestFiles = $this->filterStubbed($files);
        if (0 === count($requestFiles)) {
            return $files;
        }

        $promises = array_map(
            fn (DrupalOrgFile $file): Promise => $this->api->getFileAsync($file),
            $requestFiles,
        );
        $this->logger->debug('Batched file requests for {ids}', [
            'ids' => implode(', ', array_map(
                fn (DrupalOrgFile $file): int => $file->id(),
                $requestFiles,
            )),
        ]);
        $responses = $this->unwrap($promises);
        foreach ($responses as $key => $response) {
            $requestFiles[$key]->importResponse($response);
        }

        return $files;
    }

    /**
     * @param \dogit\DrupalOrg\Objects\DrupalOrgPatch[] $patches
     *   Patches must not be stubs
     *
     * @return \dogit\DrupalOrg\Objects\DrupalOrgPatch[]
     *   It's not necessary to use return value, can re-use $patches. Though return
     *   value will have its objects de-duplicated.
     */
    public function downloadPatchFiles(array $patches): array
    {
        /** @var \dogit\DrupalOrg\Objects\DrupalOrgPatch[] $requestPatches */
        $requestPatches = Utility::deduplicateDrupalOrgObjects($patches);
        if (0 === count($requestPatches)) {
            return $patches;
        }

        $promises = array_map(
            fn (DrupalOrgPatch $patch): Promise => $this->api->getPatchFileAsync($patch),
            $requestPatches,
        );
        $this->logger->debug('Batched patch requests for {ids}', [
            'ids' => implode(', ', array_map(
                fn (DrupalOrgPatch $patch): int => $patch->id(),
                $requestPatches,
            )),
        ]);
        $responses = $this->unwrap($promises);
        foreach ($responses as $key => $response) {
            $requestPatches[$key]->setContents((string) $response->getBody());
        }

        return $patches;
    }

    /**
     * @param \Http\Promise\Promise[]|iterable $promises
     *
     * @return \GuzzleHttp\Psr7\Response[]
     */
    protected function unwrap(iterable $promises)
    {
        $responses = [];
        (new EachPromise($promises, [
            'concurrency' => 4,
            'fulfilled' => function ($response, $key) use (&$responses) {
                $responses[$key] = $response;
            },
        ]))->promise()->wait();

        return $responses;
    }

    /**
     * Dont try to unstub already unstubbed.
     *
     * @template T of \dogit\DrupalOrg\Objects\DrupalOrgObject
     *
     * @param T[] $objects
     *
     * @return T[]
     */
    protected function filterStubbed(array $objects): array
    {
        return array_filter($objects, fn (DrupalOrgObject $object): bool => $object->isStub());
    }
}
