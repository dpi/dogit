<?php

declare(strict_types=1);

namespace dogit\Git;

use dogit\DrupalOrg\Objects\DrupalOrgPatch;

final class GitResolver
{
    public function __construct(public DrupalOrgPatch $patch, public GitOperator $gitIo)
    {
    }

    public function getHash(): string
    {
        $created = $this->patch->getCreated();
        // Returns a git hash.
        $return = $this->gitIo->execute([
            'rev-list',
            '-1',
            sprintf('--before="%s"', $created->getTimestamp()),
            // e.g 'remotes/origin/9.1.x'.
            sprintf('remotes/origin/%s', $this->patch->getGitReference()),
        ]);

        if (!$return) {
            throw new \Exception('Failed to get hash.');
        }

        return reset($return);
    }
}
