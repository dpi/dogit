<?php

declare(strict_types=1);

namespace dogit\tests\DrupalOrg\Objects;

use dogit\DrupalOrg\DrupalOrgObjectRepository;
use dogit\DrupalOrg\Objects\DrupalOrgIssue;
use dogit\tests\TestUtilities;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @coversDefaultClass \dogit\DrupalOrg\Objects\DrupalOrgObject
 */
final class DrupalOrgObjectTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @covers ::url
     */
    public function testUrl(): void
    {
        $object = DrupalOrgIssue::fromResponse(
            new Response(200, [], TestUtilities::getFixture('issue.json')),
            new DrupalOrgObjectRepository(),
        );
        $this->assertEquals('https://www.drupal.org/project/drupal/issues/2350939', $object->url());
    }

    /**
     * @covers ::isStub
     */
    public function testIsStub(): void
    {
        $object = DrupalOrgIssue::fromStub((object) ['id' => 1]);
        $this->assertTrue($object->isStub());

        $object = DrupalOrgIssue::fromResponse(
            new Response(200, [], TestUtilities::getFixture('issue.json')),
            new DrupalOrgObjectRepository(),
        );
        $this->assertFalse($object->isStub());
    }
}
