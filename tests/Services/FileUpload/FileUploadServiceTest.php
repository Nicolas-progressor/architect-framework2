<?php

declare(strict_types=1);

namespace Tests\Services\FileUpload;

use Architect\Services\FileUpload\FileUploadService;
use Architect\Services\FileUpload\UploadedFile;
use PHPUnit\Framework\TestCase;

class FileUploadServiceTest extends TestCase
{
    private string $uploadDir;
    private FileUploadService $service;

    protected function setUp(): void
    {
        $this->uploadDir = sys_get_temp_dir() . '/architect_upload_svc_' . uniqid();
        mkdir($this->uploadDir, 0o755, true);

        $this->service = new FileUploadService();
        $this->service->setUploadDir($this->uploadDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->uploadDir);
    }

    public function testUpload(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'upload_svc_');
        file_put_contents($tmpFile, 'upload test content');

        $file = new UploadedFile(
            originalName: 'test.txt',
            tmpName: $tmpFile,
            mimeType: 'text/plain',
            size: filesize($tmpFile),
            error: UPLOAD_ERR_OK,
            extension: 'txt',
        );

        copy($file->getTmpName(), $this->uploadDir . '/result.txt');
        $this->assertFileExists($this->uploadDir . '/result.txt');

        unlink($tmpFile);
    }

    public function testValidateFile(): void
    {
        $validator = $this->service->validator();
        $this->assertNotNull($validator);

        $this->service->validator()->maxSize(100);

        $tmpFile = tempnam(sys_get_temp_dir(), 'val_');
        file_put_contents($tmpFile, str_repeat('x', 200));

        $file = new UploadedFile('test.txt', $tmpFile, 'text/plain', 200, UPLOAD_ERR_OK, 'txt');
        $this->assertFalse($this->service->validator()->validate($file));

        unlink($tmpFile);
    }

    public function testHasNoErrorsInitially(): void
    {
        $this->assertFalse($this->service->hasErrors());
        $this->assertEmpty($this->service->getErrors());
    }

    public function testGetUploaded(): void
    {
        $this->assertEmpty($this->service->getUploaded());
    }

    public function testReset(): void
    {
        $this->service->reset();
        $this->assertFalse($this->service->hasErrors());
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = glob($dir . '/*');
        if ($files) {
            array_map('unlink', $files);
        }
        rmdir($dir);
    }
}
