<?php

declare(strict_types=1);

namespace dogit\tests;

class TestUtilities
{
    public static function getFixture(string $fileName): string
    {
        $fileName = sprintf(sprintf('%s/fixtures/%s', __DIR__, $fileName));
        if (!is_file($fileName)) {
            throw new \Exception(sprintf('File doesnt exist: %s', $fileName));
        }

        $contents = file_get_contents($fileName);

        if (false === $contents) {
            throw new \Exception(sprintf('Unknown fixture `%s`', $fileName));
        }

        return $contents;
    }
}
