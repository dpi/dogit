<?php

// Application for phpstan/phpstan-symfony.

require __DIR__ . '/../vendor/autoload.php';

use dogit\Commands\ProjectMergeRequest;
use dogit\Commands\IssueMergeRequest;
use dogit\Commands\ProjectCloneCommand;
use dogit\Commands\GitCommand;
use dogit\Commands\IssueTimelineCommand;
use Symfony\Component\Console\Application;

$application = new Application();

$application->add(new ProjectMergeRequest());
$application->add(new GitCommand());
$application->add(new IssueMergeRequest());
$application->add(new IssueTimelineCommand());
$application->add(new ProjectCloneCommand());

return $application;
