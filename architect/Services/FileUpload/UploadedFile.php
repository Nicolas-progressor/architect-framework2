<?php

declare(strict_types=1);

namespace Architect\Services\FileUpload;

/**
 * Uploaded file value object.
 */
class UploadedFile
{
    private bool $moved = false;

    public function __construct(
        private readonly string $originalName,
        private readonly string $tmpName,
        private readonly string $mimeType,
        private readonly int $size,
        private readonly int $error,
        private readonly ?string $extension = null,
        private readonly ?string $hash = null,
        private readonly ?string $uploadPath = null,
    ) {
    }

    /**
     * Create from $_FILES entry.
     *
     * @param array{name: string, tmp_name: string, type: string, size: int, error: int} $file
     */
    public static function fromGlobals(array $file): static
    {
        return new static(
            originalName: $file['name'] ?? '',
            tmpName: $file['tmp_name'] ?? '',
            mimeType: $file['type'] ?? '',
            size: (int) ($file['size'] ?? 0),
            error: (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE),
            extension: pathinfo($file['name'] ?? '', PATHINFO_EXTENSION),
        );
    }

    /**
     * Move the uploaded file to a destination.
     */
    public function move(string $destination): bool
    {
        if ($this->moved) {
            throw new \RuntimeException('File has already been moved');
        }

        if ($this->error !== UPLOAD_ERR_OK) {
            return false;
        }

        $dir = dirname($destination);
        if (!is_dir($dir)) {
            mkdir($dir, 0o755, true);
        }

        $result = move_uploaded_file($this->tmpName, $destination);

        if ($result) {
            $this->moved = true;
        }

        return $result;
    }

    /**
     * Move to a directory with an optional new name.
     */
    public function moveTo(string $directory, ?string $name = null): ?string
    {
        $name ??= $this->generateName();
        $destination = rtrim($directory, '/') . '/' . $name;

        return $this->move($destination) ? $destination : null;
    }

    /**
     * Check if the upload is valid.
     */
    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK && $this->tmpName !== '' && file_exists($this->tmpName);
    }

    /**
     * Get file content.
     */
    public function getContent(): ?string
    {
        if (!$this->isValid()) {
            return null;
        }

        $content = file_get_contents($this->tmpName);
        return $content !== false ? $content : null;
    }

    /**
     * Get the extension.
     */
    public function getExtension(): string
    {
        return $this->extension ?? '';
    }

    /**
     * Get the original file name.
     */
    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    /**
     * Get the temporary file path.
     */
    public function getTmpName(): string
    {
        return $this->tmpName;
    }

    /**
     * Get the MIME type.
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Get the file size in bytes.
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Get the upload error code.
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * Get upload error message.
     */
    public function getErrorMessage(): string
    {
        return match ($this->error) {
            UPLOAD_ERR_OK => 'No error',
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary directory',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
            default => 'Unknown upload error',
        };
    }

    /**
     * Generate a unique file name.
     */
    public function generateName(int $length = 16): string
    {
        $ext = $this->extension !== null && $this->extension !== '' ? '.' . $this->extension : '';
        return bin2hex(random_bytes($length / 2)) . $ext;
    }

    /**
     * Check if file has been moved.
     */
    public function isMoved(): bool
    {
        return $this->moved;
    }
}
