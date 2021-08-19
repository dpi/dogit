<?php

declare(strict_types=1);

namespace dogit\Commands;

use CzProject\GitPhp\Git;
use CzProject\GitPhp\IRunner;
use dogit\Commands\Options\ProjectCloneCommandOptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

/**
 * Clones a Drupal.org project.
 */
class ProjectCloneCommand extends Command
{
    use Traits\HttpTrait;

    protected static $defaultName = 'project:clone';
    protected Git $git;
    protected Finder $finder;

    public function __construct(IRunner $runner = null, ?Finder $finder = null)
    {
        parent::__construct();
        $this->git = $this->git($runner ?? new \dogit\Git\CliRunner());
        $this->finder = $finder ?? new Finder();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(ProjectCloneCommandOptions::ARGUMENT_PROJECT, InputArgument::REQUIRED)
            ->addArgument(ProjectCloneCommandOptions::ARGUMENT_DIRECTORY, InputArgument::OPTIONAL)
            ->addOption(ProjectCloneCommandOptions::OPTION_HTTP, null, InputOption::VALUE_NONE, 'Use HTTP instead of SSH.')
            ->addOption(ProjectCloneCommandOptions::OPTION_BRANCH, 'b', InputOption::VALUE_REQUIRED, 'Clone a specific branch. Omit to clone default branch.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $options = ProjectCloneCommandOptions::fromInput($input);

        $url = sprintf($options->isHttp ? 'https://git.drupalcode.org/project/%s.git' : 'git@git.drupal.org:project/%s.git', $options->project);
        $params = [];
        if (!empty($options->branch)) {
            $params['-b'] = $options->branch;
        }

        // When directory isnt provided then use a directory with the same name as the project, if the directory
        // doesn't exist.
        $directory = $options->directory;
        if (!$directory) {
            $directory = $options->project;
        }

        $this->git->cloneRepository(
            $url,
            $directory,
            $params,
        );

        $io->success('Done');

        return static::SUCCESS;
    }

    protected function git(IRunner $runner): Git
    {
        return new Git($runner);
    }
}
