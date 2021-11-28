<?php

declare(strict_types=1);

namespace dogit\Git;

use CzProject\GitPhp\CommandProcessor;
use CzProject\GitPhp\Runners\CliRunner as CzCliRunner;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class CliRunner extends CzCliRunner implements CliRunnerInterface
{
    private CommandProcessor $commandProcessor;
    private LoggerInterface $logger;

    public function __construct(
        ?CommandProcessor $commandProcessor = null,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct();
        $this->logger = $logger ?? new NullLogger();
        $this->commandProcessor = $commandProcessor ?? new CommandProcessor();
    }

    public function run($cwd, array $args, array $env = null)
    {
        foreach ($args as $arg) {
            if (!is_array($arg) || 2 !== count($arg)) {
                continue;
            }

            [$k, $v] = $arg;

            // Adds attribution to Dogit for Git merges.
            if ('--date' === $k) {
                if (!isset($env)) {
                    $env = [];
                }

                $env['GIT_COMMITTER_NAME'] = 'dogit';
                $env['GIT_COMMITTER_EMAIL'] = 'dogit@dogit.dev';
                $env['GIT_COMMITTER_DATE'] = $v;
            }
        }

        $this->logger->debug('Executing command: {command}', [
            'command' => $this->commandProcessor->process('git', $args),
        ]);

        return parent::run($cwd, $args, $env);
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
