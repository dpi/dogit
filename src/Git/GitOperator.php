<?php

declare(strict_types=1);

namespace dogit\Git;

use CzProject\GitPhp\Git;
use CzProject\GitPhp\GitException;
use CzProject\GitPhp\GitRepository;
use Symfony\Component\Finder\Finder;

final class GitOperator
{
    public function __construct(protected GitRepository $gitRepository)
    {
    }

    public function isClean(): bool
    {
        return empty($this->gitRepository->execute('status', '--porcelain'));
    }

    public function resetHard(?string $treeIsh = null): bool
    {
        $args = ['reset', '--hard', '--quiet'];
        if ($treeIsh) {
            $args[] = $treeIsh;
        }

        return empty($this->gitRepository->execute(...$args));
    }

    public function clean(): void
    {
        $this->gitRepository->execute(['clean', '-f']);
    }

    public function checkoutNew(string $branchName, string $startPoint, bool $track = false): void
    {
        $args = ['checkout'];
        if ($track) {
            $args[] = '--track';
        }
        $args[] = '-b';
        $args[] = $branchName;
        $args[] = $startPoint;
        $this->gitRepository->execute(...$args);
    }

    public function checkoutPathspec(string $treeIsh, string $pathSpec): void
    {
        $this->gitRepository->execute([
            'checkout',
            $treeIsh,
            // @see https://git-scm.com/docs/git-checkout#Documentation/git-checkout.txt---.
            '--',
            $pathSpec,
        ]);
    }

    public function mergeStrategyOurs(string $hash): void
    {
        $this->gitRepository->merge($hash, [
            '--strategy-option=ours',
        ]);
    }

    /**
     * @param mixed[] $options
     */
    public function commit(array $options = []): void
    {
        $this->gitRepository->execute(
            'commit',
            ...$options
        );
    }

    public function getLastCommitId(): string
    {
        return (string) $this->gitRepository->getLastCommitId();
    }

    public function addAllChanges(): void
    {
        $this->gitRepository->addAllChanges();
    }

    public function branchExists(string $branchName): bool
    {
        try {
            $this->gitRepository->execute([
                'rev-parse',
                '--verify',
                '--quiet',
                $branchName,
            ]);
        } catch (GitException) {
            return false;
        }

        return true;
    }

    public function deleteBranch(string $branchName): void
    {
        $this->gitRepository->execute(...[
            'branch',
            '-D',
            $branchName,
        ]);
    }

    public function renameBranch(string $newBranchName, string $oldBranchName = null): void
    {
        $args = ['branch', '-M'];
        if ($oldBranchName) {
            $args[] = $oldBranchName;
        }
        $args[] = $newBranchName;
        $this->gitRepository->execute(...$args);
    }

    /**
     * @return string[]
     */
    public function getNewFiles(string $object): array
    {
        return $this->gitRepository->execute([
            'show',
            $object,
            '--name-only',
            '--diff-filter=AR',
            '--no-commit-id',
        ]);
    }

    /**
     * @return string[]
     */
    public function getRemotes(): array
    {
        return $this->gitRepository->execute('remote');
    }

    /**
     * @return string[]
     */
    public function getRemoteUrls(string $remoteName): array
    {
        return $this->gitRepository->execute('remote', 'get-url', $remoteName);
    }

    public function addRemote(string $remoteName, string $gitUrl): void
    {
        $this->gitRepository->addRemote($remoteName, $gitUrl);
    }

    public function fetchRemote(string $remoteName): void
    {
        $this->gitRepository->fetch($remoteName);
    }

    public function checkoutNewTrackCustom(string $branch, string $remoteName, string $remoteBranch): void
    {
        $this->gitRepository->execute(
            'checkout',
            '-b',
            $branch,
            '--track',
            sprintf('%s/%s', $remoteName, $remoteBranch),
        );
    }

    /**
     * @param mixed ...$args
     *
     * @return string[]
     */
    public function execute(...$args): array
    {
        return $this->gitRepository->execute(...$args);
    }

    /**
     * @return string[]
     */
    public function getBranches(): array
    {
        return array_filter((array) $this->gitRepository->getBranches());
    }

    public function getRepositoryPath(): string
    {
        return $this->gitRepository->getRepositoryPath();
    }

    /**
     * @throws \CzProject\GitPhp\GitException
     *   On error, of if directory is not a Git repository
     */
    public static function fromDirectory(Git $git, string $directory, Finder $finder): static
    {
        $gitFinder = $finder->directories()->in($directory);
        if (1 !== $gitFinder->ignoreVCS(false)->ignoreDotFiles(false)->depth(0)->name(['.git'])->count()) {
            throw new GitException('Directory is not the root of a Git repository.');
        }

        $repo = $git->open($directory);

        return new static($repo);
    }
}
