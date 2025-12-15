<?php

namespace Weslinkde\PostgresTools\Support;

use FilesystemIterator;
use Throwable;

class TemporaryDirectory
{
    protected string $location;

    protected string $name = '';

    public function __construct(string $location = '')
    {
        $this->location = $this->sanitizePath($location);
    }

    public function create(): self
    {
        if ($this->location === '' || $this->location === '0') {
            $this->location = $this->getSystemTemporaryDirectory();
        }

        if ($this->name === '' || $this->name === '0') {
            $this->name = mt_rand().'-'.str_replace([' ', '.'], '', microtime());
        }

        if (! file_exists($this->getFullPath())) {
            mkdir($this->getFullPath(), 0777, true);
        }

        return $this;
    }

    public function path(string $pathOrFilename = ''): string
    {
        if ($pathOrFilename === '' || $pathOrFilename === '0') {
            return $this->getFullPath();
        }

        $path = $this->getFullPath().DIRECTORY_SEPARATOR.trim($pathOrFilename, '/');

        $directoryPath = $this->removeFilenameFromPath($path);

        if (! file_exists($directoryPath)) {
            mkdir($directoryPath, 0777, true);
        }

        return $path;
    }

    public function delete(): bool
    {
        return $this->deleteDirectory($this->getFullPath());
    }

    protected function getFullPath(): string
    {
        return $this->location.($this->name === '' || $this->name === '0' ? '' : DIRECTORY_SEPARATOR.$this->name);
    }

    protected function getSystemTemporaryDirectory(): string
    {
        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
    }

    protected function sanitizePath(string $path): string
    {
        $path = rtrim($path);

        return rtrim($path, DIRECTORY_SEPARATOR);
    }

    protected function removeFilenameFromPath(string $path): string
    {
        if (! $this->isFilePath($path)) {
            return $path;
        }

        return substr($path, 0, strrpos($path, DIRECTORY_SEPARATOR));
    }

    protected function isFilePath(string $path): bool
    {
        return str_contains($path, '.');
    }

    protected function deleteDirectory(string $path): bool
    {
        try {
            if (is_link($path)) {
                return unlink($path);
            }

            if (! file_exists($path)) {
                return true;
            }

            if (! is_dir($path)) {
                return unlink($path);
            }

            foreach (new FilesystemIterator($path) as $item) {
                if (! $this->deleteDirectory((string) $item)) {
                    return false;
                }
            }

            /*
             * By forcing a php garbage collection cycle using gc_collect_cycles() we can ensure
             * that the rmdir does not fail due to files still being reserved in memory.
             */
            gc_collect_cycles();

            return rmdir($path);
        } catch (Throwable) {
            return false;
        }
    }
}
