<?php

declare(strict_types=1);

namespace Tests\Services\Mvc;

use Architect\Services\Mvc\Http\Response;
use Architect\Services\Mvc\Http\Stream;
use PHPUnit\Framework\TestCase;

class StreamTest extends TestCase
{
    public function testCreateEmptyStream(): void
    {
        $stream = new Stream();
        $this->assertSame('', (string) $stream);
        $this->assertSame(0, $stream->getSize());
    }

    public function testCreateStreamWithContent(): void
    {
        $stream = new Stream('hello');
        $this->assertSame('hello', (string) $stream);
        $this->assertSame(5, $stream->getSize());
    }

    public function testGetContents(): void
    {
        $stream = new Stream('test content');
        $contents = $stream->getContents();
        $this->assertSame('test content', $contents);
    }

    public function testWrite(): void
    {
        $stream = new Stream();
        $written = $stream->write('hello world');
        $this->assertSame(11, $written);
        $this->assertSame('hello world', (string) $stream);
    }

    public function testRead(): void
    {
        $stream = new Stream('hello world');
        $chunk = $stream->read(5);
        $this->assertSame('hello', $chunk);
    }

    public function testIsSeekable(): void
    {
        $stream = new Stream('test');
        $this->assertTrue($stream->isSeekable());
    }

    public function testIsWritable(): void
    {
        $stream = new Stream('test');
        $this->assertTrue($stream->isWritable());
    }

    public function testIsReadable(): void
    {
        $stream = new Stream('test');
        $this->assertTrue($stream->isReadable());
    }

    public function testSeekAndTell(): void
    {
        $stream = new Stream('hello world');
        $stream->seek(6);
        $this->assertSame(6, $stream->tell());
        $this->assertSame('world', $stream->read(5));
    }

    public function testRewind(): void
    {
        $stream = new Stream('hello');
        $stream->read(3);
        $this->assertSame(3, $stream->tell());
        $stream->rewind();
        $this->assertSame(0, $stream->tell());
    }

    public function testEof(): void
    {
        $stream = new Stream('hi');
        $this->assertFalse($stream->eof());
        $stream->read(100);
        $this->assertTrue($stream->eof());
    }

    public function testDetach(): void
    {
        $stream = new Stream('hello');
        $resource = $stream->detach();
        $this->assertIsResource($resource);
        $this->assertSame([], $stream->getMetadata());
    }

    public function testClose(): void
    {
        $stream = new Stream('hello');
        $stream->close();
        $this->assertTrue($stream->eof());
    }

    public function testGetMetadata(): void
    {
        $stream = new Stream('hello');
        $metadata = $stream->getMetadata();
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('seekable', $metadata);
    }

    public function testGetMetadataByKey(): void
    {
        $stream = new Stream('hello');
        $this->assertNotNull($stream->getMetadata('seekable'));
    }

    public function testGetSize(): void
    {
        $stream = new Stream('hello world');
        $this->assertSame(11, $stream->getSize());
    }
}
