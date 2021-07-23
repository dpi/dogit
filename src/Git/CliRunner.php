<?php

declare(strict_types=1);

namespace dogit\Git;

use CzProject\GitPhp\Runners\CliRunner as CzCliRunner;

final class CliRunner extends CzCliRunner
{
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

        return parent::run($cwd, $args, $env);
    }
}
