<?php

declare(strict_types=1);

namespace dogit\Commands\Options;

use Composer\Semver\VersionParser;
use Symfony\Component\Console\Input\InputInterface;

final class GitCommandOptions
{
    public const ARGUMENT_ISSUE_ID = 'issue-id';
    public const ARGUMENT_VERSION_CONSTRAINTS = 'version-constraints';
    public const ARGUMENT_WORKING_DIRECTORY = 'working-directory';
    public const OPTION_BRANCH = 'branch';
    public const OPTION_COOKIE = 'cookie';
    public const OPTION_DELETE_EXISTING_BRANCH = 'delete-existing-branch';
    public const OPTION_EXCLUDE = 'exclude';
    public const OPTION_LAST = 'last';
    public const OPTION_NO_CACHE = 'no-cache';
    public const OPTION_PATCH_LEVEL = 'patch-level';
    public const OPTION_RESET = 'reset';

    public bool $branchDeleteExisting;
    public ?string $branchName;
    /**
     * @var string[]
     */
    public array $cookies;

    /**
     * @var string[]
     */
    public array $excludeComments;
    public string $gitDirectory;
    public int $nid;
    public bool $noHttpCache;
    public bool $onlyLastPatch;
    public int $patchLevel;
    public bool $resetUnclean;
    public string $versionConstraints;

    /**
     * @throws \UnexpectedValueException
     */
    public static function fromInput(InputInterface $input): GitCommandOptions
    {
        $instance = new static();

        $instance->branchName = $input->getOption(static::OPTION_BRANCH);
        $instance->branchDeleteExisting = (bool) $input->getOption(static::OPTION_DELETE_EXISTING_BRANCH);
        $instance->cookies = $input->getOption(static::OPTION_COOKIE);
        $instance->excludeComments = $input->getOption(static::OPTION_EXCLUDE);
        $instance->gitDirectory = (string) ($input->getArgument(static::ARGUMENT_WORKING_DIRECTORY) ?? getcwd());

        $nid = $input->getArgument(static::ARGUMENT_ISSUE_ID);

        $instance->nid = (1 === preg_match('/^\d{1,10}$/m', $nid)) ? (int) $nid : throw new \UnexpectedValueException('Issue ID is not valid');
        $instance->noHttpCache = (bool) $input->getOption(static::OPTION_NO_CACHE);
        $instance->onlyLastPatch = $input->getOption(static::OPTION_LAST);

        $patchLevel = $input->getOption(static::OPTION_PATCH_LEVEL);
        $instance->patchLevel = (1 === preg_match('/^\d$/m', (string) $patchLevel)) ? (int) $patchLevel : throw new \UnexpectedValueException('Patch level is not valid');
        $instance->resetUnclean = (bool) $input->getOption(static::OPTION_RESET);

        // Check is a proper Composer constraint. e.g 8.8.x is not accepted:
        $constraint = (string) $input->getArgument(static::ARGUMENT_VERSION_CONSTRAINTS);
        if (!empty($constraint)) {
            try {
                (new VersionParser())->parseConstraints($constraint);
            } catch (\UnexpectedValueException $e) {
                throw $e;
            } catch (\Exception $e) {
                throw new \UnexpectedValueException('Failed to parse constraint.', 0, $e);
            }
        }
        $instance->versionConstraints = $constraint;

        return $instance;
    }
}
