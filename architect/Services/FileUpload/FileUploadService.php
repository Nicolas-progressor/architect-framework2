<?php

declare(strict_types=1);

namespace Architect\Services\FileUpload;

/**
 * File upload service — handles file uploads with validation and storage.
 */
class FileUploadService
{
    private string $uploadDir = '';

    private FileUploadValidator $validator;

    /** @var array<int, string> Errors */
    private array $errors = [];

    /** @var array<int, array{path: string, name: string, file: UploadedFile}> Uploaded files info */
    private array $uploaded = [];

    public function __construct()
    {
        $this->validator = new FileUploadValidator();
    }

    /**
     * Set the upload directory.
     */
    public function setUploadDir(string $dir): static
    {
        $this->uploadDir = rtrim($dir, '/');
        return $this;
    }

    /**
     * Get the validator instance for configuration.
     */
    public function validator(): FileUploadValidator
    {
        return $this->validator;
    }

    /**
     * Upload a single file.
     */
    public function upload(UploadedFile $file, ?string $name = null): ?string
    {
        $this->errors = [];
        $this->uploaded = [];

        if (!$this->validator->validate($file)) {
            $this->errors = $this->validator->getErrors();
            return null;
        }

        $path = $file->moveTo($this->uploadDir, $name);

        if ($path !== null) {
            $this->uploaded[] = [
                'path' => $path,
                'name' => basename($path),
                'file' => $file,
            ];
        } else {
            $this->errors[] = 'Failed to move uploaded file';
        }

        return $path;
    }

    /**
     * Upload multiple files at once.
     *
     * @param array<int, UploadedFile> $files
     * @return array<int, string|null>
     */
    public function uploadMany(array $files): array
    {
        $results = [];

        foreach ($files as $file) {
            $results[] = $this->upload($file);
        }

        return $results;
    }

    /**
     * Upload from $_FILES entry.
     *
     * @param array{name: string|array, tmp_name: string|array, type: string|array, size: int|array, error: int|array} $fileEntry
     * @param string|null $name
     * @return string|array|null
     */
    public function uploadFromGlobal(array $fileEntry, ?string $name = null): string|array|null
    {
        // Handle array format (multiple files)
        if (is_array($fileEntry['name'] ?? null)) {
            return $this->uploadFromGlobalArray($fileEntry);
        }

        $file = UploadedFile::fromGlobals($fileEntry);
        return $this->upload($file, $name);
    }

    /**
     * Get errors from the last upload.
     *
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if there were errors.
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get info about uploaded files.
     *
     * @return array<int, array{path: string, name: string, file: UploadedFile}>
     */
    public function getUploaded(): array
    {
        return $this->uploaded;
    }

    /**
     * Reset state.
     */
    public function reset(): void
    {
        $this->errors = [];
        $this->uploaded = [];
    }

    /**
     * Upload multiple files from nested $_FILES format.
     *
     * @param array{name: array, tmp_name: array, type: array, size: array, error: array} $fileEntry
     * @return array<int, string|null>
     */
    private function uploadFromGlobalArray(array $fileEntry): array
    {
        $results = [];
        $names = (array) ($fileEntry['name'] ?? []);
        $tmpNames = (array) ($fileEntry['tmp_name'] ?? []);
        $types = (array) ($fileEntry['type'] ?? []);
        $sizes = (array) ($fileEntry['size'] ?? []);
        $errors = (array) ($fileEntry['error'] ?? []);

        $count = count($names);

        for ($i = 0; $i < $count; $i++) {
            $fileData = [
                'name' => $names[$i] ?? '',
                'tmp_name' => $tmpNames[$i] ?? '',
                'type' => $types[$i] ?? '',
                'size' => (int) ($sizes[$i] ?? 0),
                'error' => (int) ($errors[$i] ?? UPLOAD_ERR_NO_FILE),
            ];

            $file = UploadedFile::fromGlobals($fileData);
            $results[] = $this->upload($file);
        }

        return $results;
    }
}
