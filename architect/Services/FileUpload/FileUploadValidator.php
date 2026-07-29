<?php

declare(strict_types=1);

namespace Architect\Services\FileUpload;

/**
 * File upload validation rules.
 */
class FileUploadValidator
{
    /** @var array<int, string> Allowed MIME types */
    private array $allowedMimeTypes = [];

    /** @var array<int, string> Allowed extensions */
    private array $allowedExtensions = [];

    private int $maxSize = 0;

    private int $minSize = 0;

    /** @var array<int, string> Validation errors */
    private array $errors = [];

    /**
     * Set allowed MIME types.
     */
    public function allowedMimeTypes(array $mimeTypes): static
    {
        $this->allowedMimeTypes = $mimeTypes;
        return $this;
    }

    /**
     * Set allowed file extensions.
     */
    public function allowedExtensions(array $extensions): static
    {
        $this->allowedExtensions = $extensions;
        return $this;
    }

    /**
     * Set maximum file size in bytes.
     */
    public function maxSize(int $bytes): static
    {
        $this->maxSize = $bytes;
        return $this;
    }

    /**
     * Set minimum file size in bytes.
     */
    public function minSize(int $bytes): static
    {
        $this->minSize = $bytes;
        return $this;
    }

    /**
     * Allow images only (jpeg, png, gif, webp).
     */
    public function onlyImages(): static
    {
        return $this
            ->allowedMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
            ->allowedExtensions(['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }

    /**
     * Allow documents (pdf, doc, docx, xls, xlsx).
     */
    public function onlyDocuments(): static
    {
        return $this
            ->allowedMimeTypes([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->allowedExtensions(['pdf', 'doc', 'docx', 'xls', 'xlsx']);
    }

    /**
     * Validate an uploaded file.
     */
    public function validate(UploadedFile $file): bool
    {
        $this->errors = [];

        if (!$file->isValid()) {
            $this->errors[] = $file->getErrorMessage();
            return false;
        }

        if ($this->maxSize > 0 && $file->getSize() > $this->maxSize) {
            $this->errors[] = sprintf(
                'File exceeds maximum size of %s bytes',
                number_format($this->maxSize)
            );
        }

        if ($this->minSize > 0 && $file->getSize() < $this->minSize) {
            $this->errors[] = sprintf(
                'File is below minimum size of %s bytes',
                number_format($this->minSize)
            );
        }

        if (!empty($this->allowedExtensions)) {
            $ext = strtolower($file->getExtension());
            if (!in_array($ext, $this->allowedExtensions, true)) {
                $this->errors[] = sprintf(
                    'File extension "%s" is not allowed. Allowed: %s',
                    $ext,
                    implode(', ', $this->allowedExtensions)
                );
            }
        }

        if (!empty($this->allowedMimeTypes)) {
            $detectedMime = $file->getMimeType();
            if ($detectedMime !== '' && !in_array($detectedMime, $this->allowedMimeTypes, true)) {
                $this->errors[] = sprintf(
                    'File MIME type "%s" is not allowed.',
                    $detectedMime
                );
            }
        }

        return empty($this->errors);
    }

    /**
     * Get validation errors.
     *
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if there are errors.
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}
