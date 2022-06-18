<?php

declare(strict_types=1);

namespace dogit\Commands\Options;

use Symfony\Component\Console\Input\InputInterface;

final class ProjectMergeRequestOptions
{
    public const ARGUMENT_DIRECTORY = 'directory';
    public const ARGUMENT_PROJECT = 'project';
    public const OPTION_ALL = 'all';
    public const OPTION_BRANCH = 'branch';
    public const OPTION_HTTP = 'http';
    public const OPTION_ONLY_CLOSED = 'include-closed';
    public const OPTION_ONLY_MERGED = 'include-merged';
    public const OPTION_NO_CACHE = 'no-cache';

    public string $directory;
    public ?string $project;
    public ?string $branchName;
    public bool $includeAll;
    public bool $isHttp;
    public bool $onlyClosed;
    public bool $onlyMerged;
    public bool $noHttpCache;

    public static function fromInput(InputInterface $input): static
    {
        $instance = new static();

        $instance->branchName = $input->getOption(static::OPTION_BRANCH);
        $directory = (string) $input->getArgument(static::ARGUMENT_DIRECTORY);
        $cwd = \getcwd();
        $instance->directory = ('.' === $directory && is_string($cwd)) ? $cwd : $directory;
        $instance->project = $input->getArgument(static::ARGUMENT_PROJECT);
        $instance->isHttp = $input->getOption(static::OPTION_HTTP);
        $instance->includeAll = (bool) $input->getOption(static::OPTION_ALL);
        $instance->onlyClosed = (bool) $input->getOption(static::OPTION_ONLY_CLOSED);
        $instance->onlyMerged = (bool) $input->getOption(static::OPTION_ONLY_MERGED);
        $instance->noHttpCache = (bool) $input->getOption(static::OPTION_NO_CACHE);

        return $instance;
    }
}
