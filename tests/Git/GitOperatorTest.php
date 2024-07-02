<?php

declare(strict_types=1);

namespace dogit\tests\Git;

use CzProject\GitPhp\GitRepository;
use dogit\Git\GitOperator;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @coversDefaultClass \dogit\Git\GitOperator
 */
final class GitOperatorTest extends \dogit\tests\DogitTestBase
{
    use ProphecyTrait;

    /**
     * @covers ::isClean
     */
    public function testIsClean(): void
    {
        $gitRepository = $this->createMock(GitRepository::class);
        $gitRepository->expects($this->once())
            ->method('execute')
            ->with('status', '--porcelain')
            ->willReturn([]);
        $gitOperator = new GitOperator($gitRepository);
        $this->assertTrue($gitOperator->isClean());
    }

    /**
     * @covers ::isClean
     */
    public function testIsNotClean(): void
    {
        $gitRepository = $this->createMock(GitRepository::class);
        $gitRepository->expects($this->once())
            ->method('execute')
            ->with('status', '--porcelain')
            ->willReturn(['?? hey.txt']);
        $gitOperator = new GitOperator($gitRepository);
        $this->assertFalse($gitOperator->isClean());
    }
}
