<?php

declare(strict_types=1);

namespace dogit\Commands\Options;

use Symfony\Component\Console\Input\InputInterface;

final class ProjectCloneCommandOptions
{
    public const ARGUMENT_DIRECTORY = 'directory';
    public const ARGUMENT_PROJECT = 'project';
    public const OPTION_BRANCH = 'branch';
    public const OPTION_HTTP = 'http';

    public string $directory;
    public string $project;
    public ?string $branch;
    public bool $isHttp;

    public static function fromInput(InputInterface $input): ProjectCloneCommandOptions
    {
        $instance = new static();

        $instance->directory = $input->getArgument(static::ARGUMENT_DIRECTORY);
        $instance->project = $input->getArgument(static::ARGUMENT_PROJECT);
        $instance->branch = $input->getOption(static::OPTION_BRANCH) ?? null;
        $instance->isHttp = $input->getOption(static::OPTION_HTTP);

        return $instance;
    }
}
