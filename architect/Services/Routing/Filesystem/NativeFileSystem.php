<?php

declare(strict_types=1);

namespace Architect\Services\Routing\Filesystem;

use Architect\Services\Routing\Contracts\FileSystemInterface;

/**
 * Native PHP file system implementation.
 */
final class NativeFileSystem implements FileSystemInterface
{
    /**
     * @inheritdoc
     */
    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * @inheritdoc
     */
    public function isDir(string $path): bool
    {
        return is_dir($path);
    }

    /**
     * @inheritdoc
     */
    public function glob(string $pattern): array
    {
        $result = glob($pattern);
        return $result !== false ? $result : [];
    }

    /**
     * @inheritdoc
     */
    public function read(string $path): ?string
    {
        $content = file_get_contents($path);
        return $content !== false ? $content : null;
    }

    /**
     * @inheritdoc
     */
    public function json(string $path): ?array
    {
        $content = $this->read($path);
        if ($content === null) {
            return null;
        }

        $data = json_decode($content, true);
        return is_array($data) ? $data : null;
    }
}
