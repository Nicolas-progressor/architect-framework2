<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Http;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * PSR-7 Stream implementation.
 * 
 * Provides a stream wrapper for string content.
 * 
 * @package Architect\Services\Mvc\Http
 */
class Stream implements StreamInterface
{
    /** @var resource|null Stream resource */
    private $resource;

    /** @var int Size of the stream */
    private ?int $size = null;

    /**
     * Create stream from string content.
     * 
     * @param string $content Stream content
     */
    public function __construct(string $content = '')
    {
        $resource = fopen('php://temp', 'r+');
        
        if ($resource === false) {
            throw new RuntimeException('Failed to create stream');
        }

        $this->resource = $resource;
        $this->write($content);
        $this->rewind();
    }

    /**
     * Create stream from file.
     * 
     * @param string $filename File path
     * @param string $mode Open mode
     * @return self
     */
    public static function fromFile(string $filename, string $mode = 'r'): self
    {
        if (!file_exists($filename)) {
            throw new RuntimeException("File not found: {$filename}");
        }

        $resource = fopen($filename, $mode);

        if ($resource === false) {
            throw new RuntimeException("Failed to open file: {$filename}");
        }

        $stream = new self();
        $stream->resource = $resource;
        $stream->size = null;

        return $stream;
    }

    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        try {
            if ($this->isSeekable()) {
                $this->rewind();
            }
            return $this->getContents();
        } catch (RuntimeException) {
            return '';
        }
    }

    /**
     * @inheritdoc
     */
    public function close(): void
    {
        if ($this->resource !== null) {
            fclose($this->resource);
            $this->resource = null;
        }
    }

    /**
     * @inheritdoc
     */
    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;
        $this->size = null;

        return $resource;
    }

    /**
     * @inheritdoc
     */
    public function getSize(): ?int
    {
        if ($this->size !== null) {
            return $this->size;
        }

        if ($this->resource === null) {
            return null;
        }

        $stats = fstat($this->resource);
        $this->size = $stats !== false ? $stats['size'] : null;

        return $this->size;
    }

    /**
     * @inheritdoc
     */
    public function tell(): int
    {
        if ($this->resource === null) {
            throw new RuntimeException('Stream is detached');
        }

        $position = ftell($this->resource);

        if ($position === false) {
            throw new RuntimeException('Unable to determine stream position');
        }

        return $position;
    }

    /**
     * @inheritdoc
     */
    public function eof(): bool
    {
        if ($this->resource === null) {
            return true;
        }

        return feof($this->resource);
    }

    /**
     * @inheritdoc
     */
    public function isSeekable(): bool
    {
        if ($this->resource === null) {
            return false;
        }

        $metadata = stream_get_meta_data($this->resource);
        return $metadata['seekable'];
    }

    /**
     * @inheritdoc
     */
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if ($this->resource === null) {
            throw new RuntimeException('Stream is detached');
        }

        if (!$this->isSeekable()) {
            throw new RuntimeException('Stream is not seekable');
        }

        if (fseek($this->resource, $offset, $whence) === -1) {
            throw new RuntimeException('Unable to seek to stream position');
        }
    }

    /**
     * @inheritdoc
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * @inheritdoc
     */
    public function isWritable(): bool
    {
        if ($this->resource === null) {
            return false;
        }

        $metadata = stream_get_meta_data($this->resource);
        $mode = $metadata['mode'];

        return str_contains($mode, 'w') 
            || str_contains($mode, 'r+') 
            || str_contains($mode, 'x') 
            || str_contains($mode, 'a');
    }

    /**
     * @inheritdoc
     */
    public function write(string $string): int
    {
        if ($this->resource === null) {
            throw new RuntimeException('Stream is detached');
        }

        if (!$this->isWritable()) {
            throw new RuntimeException('Stream is not writable');
        }

        $written = fwrite($this->resource, $string);

        if ($written === false) {
            throw new RuntimeException('Unable to write to stream');
        }

        $this->size = null;

        return $written;
    }

    /**
     * @inheritdoc
     */
    public function isReadable(): bool
    {
        if ($this->resource === null) {
            return false;
        }

        $metadata = stream_get_meta_data($this->resource);
        $mode = $metadata['mode'];

        return str_contains($mode, 'r') || str_contains($mode, '+');
    }

    /**
     * @inheritdoc
     */
    public function read(int $length): string
    {
        if ($this->resource === null) {
            throw new RuntimeException('Stream is detached');
        }

        if (!$this->isReadable()) {
            throw new RuntimeException('Stream is not readable');
        }

        $content = fread($this->resource, $length);

        if ($content === false) {
            throw new RuntimeException('Unable to read from stream');
        }

        return $content;
    }

    /**
     * @inheritdoc
     */
    public function getContents(): string
    {
        if ($this->resource === null) {
            throw new RuntimeException('Stream is detached');
        }

        if (!$this->isReadable()) {
            throw new RuntimeException('Stream is not readable');
        }

        $content = stream_get_contents($this->resource);

        if ($content === false) {
            throw new RuntimeException('Unable to read stream contents');
        }

        return $content;
    }

    /**
     * @inheritdoc
     */
    public function getMetadata(?string $key = null)
    {
        if ($this->resource === null) {
            return $key !== null ? null : [];
        }

        $metadata = stream_get_meta_data($this->resource);

        if ($key !== null) {
            return $metadata[$key] ?? null;
        }

        return $metadata;
    }
}
