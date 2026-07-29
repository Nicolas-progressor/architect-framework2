<?php

declare(strict_types=1);

namespace Tests\Services\FileUpload;

use Architect\Services\FileUpload\FileUploadValidator;
use Architect\Services\FileUpload\UploadedFile;
use PHPUnit\Framework\TestCase;

class FileUploadValidatorTest extends TestCase
{
    private FileUploadValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new FileUploadValidator();
    }

    private function createValidFile(): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'val_');
        file_put_contents($tmp, str_repeat('x', 100));
        return new UploadedFile('test.txt', $tmp, 'text/plain', 100, UPLOAD_ERR_OK, 'txt');
    }

    public function testValidPasses(): void
    {
        $file = $this->createValidFile();
        $this->assertTrue($this->validator->validate($file));
    }

    public function testInvalidFileFails(): void
    {
        $file = new UploadedFile('', '', '', 0, UPLOAD_ERR_NO_FILE);
        $this->assertFalse($this->validator->validate($file));
        $this->assertTrue($this->validator->hasErrors());
    }

    public function testMaxSize(): void
    {
        $file = $this->createValidFile();

        $this->validator->maxSize(50);
        $this->assertFalse($this->validator->validate($file));
        $this->assertStringContainsString('50', $this->validator->getErrors()[0]);
    }

    public function testMaxSizeWithinLimit(): void
    {
        $file = $this->createValidFile();

        $this->validator->maxSize(200);
        $this->assertTrue($this->validator->validate($file));
    }

    public function testMinSize(): void
    {
        $file = $this->createValidFile();

        $this->validator->minSize(500);
        $this->assertFalse($this->validator->validate($file));
    }

    public function testMinSizeWithinLimit(): void
    {
        $file = $this->createValidFile();

        $this->validator->minSize(50);
        $this->assertTrue($this->validator->validate($file));
    }

    public function testAllowedExtensions(): void
    {
        $file = $this->createValidFile();

        $this->validator->allowedExtensions(['jpg', 'png']);
        $this->assertFalse($this->validator->validate($file));

        $this->validator->allowedExtensions(['txt']);
        $this->assertTrue($this->validator->validate($file));
    }

    public function testAllowedMimeTypes(): void
    {
        $file = $this->createValidFile();

        $this->validator->allowedMimeTypes(['image/jpeg']);
        $this->assertFalse($this->validator->validate($file));

        $this->validator->allowedMimeTypes(['text/plain']);
        $this->assertTrue($this->validator->validate($file));
    }

    public function testOnlyImages(): void
    {
        $this->validator->onlyImages();

        $file = $this->createValidFile();
        $this->assertFalse($this->validator->validate($file));

        $tmpFile = tempnam(sys_get_temp_dir(), 'img_');
        file_put_contents($tmpFile, 'fake image content');

        $this->assertFalse($this->validator->validate(
            new UploadedFile('photo.txt', $tmpFile, 'text/plain', filesize($tmpFile), UPLOAD_ERR_OK, 'txt')
        ));

        $imgFile = new UploadedFile('photo.jpg', $tmpFile, 'image/jpeg', filesize($tmpFile), UPLOAD_ERR_OK, 'jpg');
        $this->assertTrue($this->validator->validate($imgFile));

        unlink($tmpFile);
    }

    public function testOnlyDocuments(): void
    {
        $this->validator->onlyDocuments();

        $tmp = tempnam(sys_get_temp_dir(), 'doc_');
        file_put_contents($tmp, 'pdf content');
        $file = $this->createValidFile();
        $this->assertFalse($this->validator->validate($file));

        $pdfFile = new UploadedFile('doc.pdf', $tmp, 'application/pdf', filesize($tmp), UPLOAD_ERR_OK, 'pdf');
        $this->assertTrue($this->validator->validate($pdfFile));

        unlink($tmp);
    }

    public function testFluentInterface(): void
    {
        $result = $this->validator
            ->allowedMimeTypes(['text/plain'])
            ->allowedExtensions(['txt'])
            ->maxSize(1024)
            ->minSize(1);

        $this->assertSame($this->validator, $result);
    }
}
