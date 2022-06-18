<?php

declare(strict_types=1);

namespace dogit\DrupalOrg\IssueGraph;

use dogit\DrupalOrg\DrupalOrgObjectRepository;
use dogit\DrupalOrg\IssueGraph\Events\CommentEvent;
use dogit\DrupalOrg\IssueGraph\Events\IssueEvent;
use dogit\DrupalOrg\IssueGraph\Events\MergeRequestCreateEvent;
use dogit\DrupalOrg\IssueGraph\Events\TestResultEvent;
use dogit\DrupalOrg\Objects\DrupalOrgComment;
use dogit\HttplugBrowser;
use Http\Client\HttpAsyncClient;
use Psr\Http\Message\RequestFactoryInterface;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Computes events for an issue.
 *
 * Crawls issue web page and extracts a graph of events consisting of comments
 * and metadata changes such as test results and issue version changes.
 *
 * Each instance represents a single issue.
 */
class DrupalOrgIssueGraph
{
    protected AbstractBrowser $browser;

    public function __construct(
        RequestFactoryInterface $httpFactory,
        HttpAsyncClient $httpClient,
        protected DrupalOrgObjectRepository $repository,
        protected string $uri,
    ) {
        $this->browser = new HttplugBrowser($httpFactory, $httpClient);
    }

    /**
     * Computes events of an issue.
     *
     * Initiates an external request.
     *
     * @return \Generator<int,\dogit\DrupalOrg\IssueGraph\Events\IssueEventInterface>
     *
     * @throws \Exception
     */
    public function graph(): \Generator
    {
        $crawler = $this->browser->request('GET', $this->uri);

        $issueForkHeading = $crawler->filter('#drupalorg-issue-forks h3 a');
        $repoUrlGit = null;
        $repoUrlHttp = null;
        $branchMrs = [];
        if (count($issueForkHeading) > 0) {
            $repoId = trim($crawler->filter('#drupalorg-issue-forks h3 a')->text());
            $repoUrlGit = sprintf('git@git.drupal.org:issue/%s.git', $repoId);
            $repoUrlHttp = sprintf('https://git.drupalcode.org/issue/%s.git', $repoId);
            $branchMrs = $this->findMergeRequestBranches($crawler);
        }

        foreach ($this->findComments($crawler) as [$commentElement, $commentStub]) {
            assert($commentElement instanceof \DOMElement);
            assert($commentStub instanceof DrupalOrgComment);

            $commentCrawler = new Crawler($commentElement);
            $commentNumber = trim($commentCrawler->filter('.submitted h3')->text());
            preg_match('/Comment #(?<sequence>\d{1,16})/', $commentNumber, $matches);
            // Could be a float, so round.
            $commentStub->setSequence((int) $matches['sequence']);

            yield new CommentEvent($commentStub);

            // Detect merge requests.
            if (count($branchMrs) > 0 && null !== $repoUrlGit && null !== $repoUrlHttp) {
                $fieldItems = $commentCrawler->filter('.field-items > *');
                foreach ($fieldItems as $fieldItem) {
                    if (str_contains($fieldItem->textContent, 'opened merge request !')) {
                        $aCrawler = new Crawler($fieldItem);
                        $aElements = $aCrawler->filter('a');
                        if (2 === count($aElements)) {
                            /** @var \DOMElement $a */
                            $a = $aElements->getNode(1);
                            $mrLink = $a->getAttribute('href');
                            $matches = [];
                            preg_match('/.*drupalcode\.org\/project\/(?<project>.*)\/-\/merge_requests\/(?<mr_id>\d{1,16})/', $mrLink, $matches);
                            $mrId = (int) ($matches['mr_id'] ?? throw new \Exception('Expected MR ID in comment.'));

                            $branchName = $branchMrs[$mrId] ?? throw new \Exception('No branch name found for MR');
                            yield new MergeRequestCreateEvent(
                                $commentStub,
                                $mrLink,
                                $matches['project'],
                                $mrId,
                                $repoUrlGit,
                                $repoUrlHttp,
                                $branchName
                            );
                        }
                    }
                }
            }

            // Changes to issues, and test runs:
            $nodeChanges = $commentCrawler->filter('table.nodechanges-field-changes tr');

            foreach ($nodeChanges as $tr) {
                $tds = array_map(
                    fn ($td) => $td->textContent,
                    iterator_to_array((new Crawler($tr))->filter('td'))
                );

                if (0 === count($tds)) {
                    continue;
                }

                $first = array_shift($tds);
                yield IssueEvent::fromRaw($commentStub, trim($first, " \t\n\r\0\x0B»:"), array_values($tds));
            }

            $tests = $commentCrawler->filter('.nodechanges-file-changes ul.pift-ci-tests');
            foreach ($tests as $test) {
                $testCrawler = new Crawler($test);
                $version = trim($testCrawler->filter('li:nth-child(1)')->text(), " \t\n\r\0\x0B»:");
                $result = trim($testCrawler->filter('li:nth-child(2)')->text());
                yield new TestResultEvent($commentStub, $version, $result);
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function findMergeRequestBranches(Crawler $crawler): array
    {
        $branchMrs = [];

        foreach ($crawler->filter('#drupalorg-issue-forks .branches > li') as $branchRow) {
            assert($branchRow instanceof \DOMElement);

            $branchName = $branchRow->getAttribute('data-branch');
            $mrLinkCrawler = new Crawler($branchRow);

            $hyperlinks = $mrLinkCrawler->filter('a.merge-request');
            if (0 === count($hyperlinks)) {
                continue;
            }

            // Can force type since count was checked above.
            $element = $hyperlinks->getNode(0);
            assert($element instanceof \DOMElement);

            $mrLink = $element->getAttribute('href');
            $matches = [];
            preg_match('/.*drupalcode\.org\/project\/(?<project>.*)\/-\/merge_requests\/(?<mr_id>\d{1,16})/', $mrLink, $matches);
            $mrId = (int) ($matches['mr_id'] ?? throw new \Exception('Expected MR ID in issue summary row.'));
            $branchMrs[$mrId] = $branchName;
        }

        return $branchMrs;
    }

    /**
     * @return array<int, array{\DOMNode, \dogit\DrupalOrg\Objects\DrupalOrgObject}>
     */
    private function findComments(Crawler $crawler): array
    {
        $comments = [];

        foreach ($crawler->filter('#content .comments > .comment') as $commentElement) {
            assert($commentElement instanceof \DOMElement);
            $id = $commentElement->getAttribute('id');
            if (0 === strlen($id)) {
                throw new \LogicException('All comments are expected to have an ID.');
            }

            preg_match('/comment-(?<cid>\d{1,16})/', $id, $matches);
            $comments[] = [$commentElement, $this->repository->share(
                DrupalOrgComment::fromStub((object) ['id' => (int) $matches['cid']])->setRepository($this->repository),
            )];
        }

        return $comments;
    }
}
