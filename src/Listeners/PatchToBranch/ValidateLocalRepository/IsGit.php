<?php

declare(strict_types=1);

namespace dogit\Listeners\PatchToBranch\ValidateLocalRepository;

use dogit\Events\PatchToBranch\ValidateLocalRepositoryEvent;

/**
 * Ensures the target directory looks like an existing Git repository.
 */
final class IsGit
{
    public function __invoke(ValidateLocalRepositoryEvent $event): void
    {
        if (0 === count($event->gitIo->getBranches())) {
            $event->logger->error(sprintf('Directory %s does not look like a Git repository.', $event->gitDirectory));
            $event->setIsInvalid();
        }
    }
}
