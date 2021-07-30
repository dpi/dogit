<?php

declare(strict_types=1);

namespace dogit\Events\GitCommand;

use dogit\DrupalOrg\DrupalOrgObjectRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class TerminateEvent extends DogitEvent
{
    public DrupalOrgObjectRepository $repository;

    public bool $isSuccess;

    public LoggerInterface $logger;

    public SymfonyStyle $io;

    public function __construct(
        SymfonyStyle $io,
        LoggerInterface $logger,
        bool $isSuccess,
        DrupalOrgObjectRepository $repository
    ) {
        $this->io = $io;
        $this->logger = $logger;
        $this->isSuccess = $isSuccess;
        $this->repository = $repository;
    }
}
