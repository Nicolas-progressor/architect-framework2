<?php

declare(strict_types=1);

namespace Architect\Services\Routing\Contracts;

/**
 * Interface for file system operations.
 */
interface FileSystemInterface
{
    /**
     * Check if file exists.
     */
    public function exists(string $path): bool;

    /**
     * Check if path is a directory.
     */
    public function isDir(string $path): bool;

    /**
     * Find files matching pattern.
     * @return string[]
     */
    public function glob(string $pattern): array;

    /**
     * Read file contents.
     */
    public function read(string $path): ?string;

    /**
     * Read and decode JSON file.
     * @return array|null
     */
    public function json(string $path): ?array;
}
