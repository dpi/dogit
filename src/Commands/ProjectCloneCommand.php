<?php

declare(strict_types=1);

namespace dogit\Commands;

use CzProject\GitPhp\Git;
use CzProject\GitPhp\Runners\CliRunner;
use dogit\Commands\Options\ProjectCloneCommandOptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Clones a Drupal.org project.
 */
final class ProjectCloneCommand extends Command
{
    use Traits\HttpTrait;

    protected static $defaultName = 'project:clone';

    protected function configure(): void
    {
        $this
            ->addArgument(ProjectCloneCommandOptions::ARGUMENT_PROJECT, InputArgument::REQUIRED)
            ->addArgument(ProjectCloneCommandOptions::ARGUMENT_DIRECTORY, InputArgument::REQUIRED)
            ->addOption(ProjectCloneCommandOptions::OPTION_HTTP, null, InputOption::VALUE_NONE, 'Use HTTP instead of SSH.')
            ->addOption(ProjectCloneCommandOptions::OPTION_BRANCH, 'b', InputOption::VALUE_REQUIRED, 'Clone a specific branch. Omit to clone default branch.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $options = ProjectCloneCommandOptions::fromInput($input);

        $runner = new CliRunner();
        $git = new Git($runner);
        $url = sprintf($options->isHttp ? 'https://git.drupalcode.org/project/%s.git' : 'git@git.drupal.org:project/%s.git', $options->project);
        $params = [];
        if (!empty($options->branch)) {
            $params['-b'] = $options->branch;
        }
        $git->cloneRepository(
            $url,
            $options->directory,
            $params,
        );

        $io->success('Done');

        return static::SUCCESS;
    }
}
