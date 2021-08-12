<?php

declare(strict_types=1);

namespace dogit\Listeners\PatchToBranch\ValidateLocalRepository;

use dogit\Events\PatchToBranch\ValidateLocalRepositoryEvent;

/**
 * Ensures the working copy is clean, and optionally clean it.
 */
final class IsClean
{
    public function __invoke(ValidateLocalRepositoryEvent $event): void
    {
        // Check if working copy is unclean.
        if ($event->gitIo->isClean()) {
            return;
        }

        $event->logger->warning('Git working copy is not clean.');
        if ($event->options->resetUnclean) {
            if ($event->gitIo->resetHard()) {
                $event->logger->info('Git working copy hard reset');
            } else {
                $event->logger->error('Git working copy is still unclean after attempting to reset. Resolve manually.');
                $event->setIsInvalid();
            }
        } else {
            $event->logger->error('Git working copy is unclean. Use --reset to automatically clean.');
            $event->setIsInvalid();
        }
    }
}
