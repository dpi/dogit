<?php

declare(strict_types=1);

namespace dogit;

use Kevinrob\GuzzleCache\CacheEntry;
use Kevinrob\GuzzleCache\Storage\CacheStorageInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToDeleteFile;

/**
 * Flysystem2 cache.
 *
 * Similar to \Kevinrob\GuzzleCache\Storage\Flysystem2Storage from
 * https://github.com/Kevinrob/guzzle-cache-middleware/pull/138/files.
 */
final class Flysystem2Storage implements CacheStorageInterface
{
    private Filesystem $filesystem;

    public function __construct(FilesystemAdapter $adapter)
    {
        $this->filesystem = new Filesystem($adapter);
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($key)
    {
        if ($this->filesystem->fileExists($key)) {
            // The file exist, read it!
            $data = @unserialize(
                $this->filesystem->read($key)
            );

            if ($data instanceof CacheEntry) {
                return $data;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function save($key, CacheEntry $data)
    {
        $this->filesystem->write($key, serialize($data));

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        try {
            $this->filesystem->delete($key);

            return true;
        } catch (UnableToDeleteFile $ex) {
            return false;
        }
    }
}
