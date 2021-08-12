<?php

declare(strict_types=1);

namespace dogit\Listeners\PatchToBranch\Terminate;

use dogit\Events\PatchToBranch\TerminateEvent;

final class EndMessage
{
    public function __invoke(TerminateEvent $event): void
    {
        if (!$event->isSuccess) {
            $event->io->error('Error');

            return;
        }

        $event->io->success('Done');
        $event->io->note('If you are considering uploading to drupal.org, please review each commit carefully.');
        $event->io->note('When pushing branches created by Dogit, maintainers may not delegate issue credit for work created by automatic tooling such as Dogit. Justify your case for credit as a comment if considerable manual effort was required to build the branch.');
        $event->io->note('Additionally, it\'s helpful to share the exact command you used to construct the branch. Especially if version constraints, comment exclusions, or patch exclusions were used.');
        $event->io->note('✨ Don\'t forget to like and subscribe — dogit.dev ✨');
    }
}
