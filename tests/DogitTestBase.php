<?php

declare(strict_types=1);

namespace dogit\tests;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\Adapter\Phpunit\MockeryTestCaseSetUp;
use PHPUnit\Framework\TestCase;

/**
 * Base class for unit testing.
 */
abstract class DogitTestBase extends TestCase
{
    use MockeryPHPUnitIntegration;
    use MockeryTestCaseSetUp;

    protected function mockeryTestSetUp(): void
    {
    }

    protected function mockeryTestTearDown(): void
    {
    }
}
