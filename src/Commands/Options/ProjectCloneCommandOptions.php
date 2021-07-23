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

    public static function fromInput(InputInterface $input): static
    {
        $instance = new static();

        // @phpstan-ignore-next-line
        $instance->directory = $input->getArgument(static::ARGUMENT_DIRECTORY);
        // @phpstan-ignore-next-line
        $instance->project = $input->getArgument(static::ARGUMENT_PROJECT);
        // @phpstan-ignore-next-line
        $instance->branch = $input->getOption(static::OPTION_BRANCH) ?? null;
        // @phpstan-ignore-next-line
        $instance->isHttp = $input->getOption(static::OPTION_HTTP);

        return $instance;
    }
}
