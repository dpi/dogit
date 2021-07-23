<?php

declare(strict_types=1);

namespace dogit\Commands\Options;

use Symfony\Component\Console\Input\InputInterface;

final class IssueCloneOptions
{
    public const ARGUMENT_DIRECTORY = 'directory';
    public const ARGUMENT_ISSUE_ID = 'issue-id';
    public const OPTION_COOKIE = 'cookie';
    public const OPTION_SINGLE = 'single';
    public const OPTION_NO_CACHE = 'no-cache';

    /**
     * @var string[]
     */
    public array $cookies;
    public string $directory;
    public int $nid;
    public bool $noHttpCache;
    public bool $single;

    public static function fromInput(InputInterface $input): static
    {
        $instance = new static();
        // @phpstan-ignore-next-line
        $instance->cookies = $input->getOption(static::OPTION_COOKIE);
        // @phpstan-ignore-next-line
        $instance->directory = (string) $input->getArgument(static::ARGUMENT_DIRECTORY);

        $nid = $input->getArgument(static::ARGUMENT_ISSUE_ID);
        // @phpstan-ignore-next-line
        $instance->nid = (1 === preg_match('/^\d{1,10}$/m', $nid)) ? (int) $nid : throw new \UnexpectedValueException('Issue ID is not valid');
        $instance->noHttpCache = (bool) $input->getOption(static::OPTION_NO_CACHE);
        $instance->single = (bool) $input->getOption(static::OPTION_SINGLE);

        return $instance;
    }
}
