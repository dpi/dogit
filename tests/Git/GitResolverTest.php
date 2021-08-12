<?php

declare(strict_types=1);

namespace dogit\tests\Git;

use CzProject\GitPhp\GitRepository;
use dogit\DrupalOrg\DrupalOrgObjectRepository;
use dogit\DrupalOrg\Objects\DrupalOrgPatch;
use dogit\Git\GitOperator;
use dogit\Git\GitResolver;
use dogit\tests\TestUtilities;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @coversDefaultClass \dogit\Git\GitResolver
 */
final class GitResolverTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @covers ::__construct
     * @covers ::getHash
     */
    public function testIsClean(): void
    {
        $gitRepository = $this->createMock(GitRepository::class);
        $gitRepository->expects($this->once())
            ->method('execute')
            ->with([
                'rev-list',
                '-1',
                '--before="1428704703"',
                'remotes/origin/8.x-1.0',
            ])
            ->willReturn(['cccccccccc000000000000000000000000000001']);
        $gitOperator = new GitOperator($gitRepository);
        $patch = DrupalOrgPatch::fromResponse(
            new Response(200, [], TestUtilities::getFixture('file-22220001.json')),
            new DrupalOrgObjectRepository(),
        );
        $patch->setGitReference('8.x-1.0');
        $gitResolver = new GitResolver($patch, $gitOperator);
        $this->assertEquals('cccccccccc000000000000000000000000000001', $gitResolver->getHash());
    }
}
