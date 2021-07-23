<?php

declare(strict_types=1);

namespace dogit\Events\GitCommand;

use dogit\DrupalOrg\DrupalOrgObjectRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class TerminateEvent extends DogitEvent
{
    public function __construct(
        public SymfonyStyle $io,
        public LoggerInterface $logger,
        public bool $isSuccess,
        public DrupalOrgObjectRepository $repository,
    ) {
    }
}
