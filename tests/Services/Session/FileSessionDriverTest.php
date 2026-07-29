<?php

declare(strict_types=1);

namespace Tests\Services\Session;

use Architect\Services\Session\Drivers\FileSessionDriver;
use PHPUnit\Framework\TestCase;

class FileSessionDriverTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/architect_session_test_' . uniqid();
        mkdir($this->tempDir, 0o755, true);
    }

    protected function tearDown(): void
    {
        $files = glob($this->tempDir . '/*');
        if ($files) {
            array_map('unlink', $files);
        }
        rmdir($this->tempDir);
    }

    public function testStartCreatesSession(): void
    {
        $session = new FileSessionDriver($this->tempDir);
        $this->assertTrue($session->start());
        $this->assertTrue($session->isActive());
    }

    public function testSetAndGetPersistsToFile(): void
    {
        $session = new FileSessionDriver($this->tempDir);
        $session->start();
        $session->set('name', 'John');
        $session->save();

        $id = $session->getId();

        $session2 = new FileSessionDriver($this->tempDir);
        $session2->setId($id);
        $session2->start();

        $this->assertSame('John', $session2->get('name'));
    }

    public function testExpiredSession(): void
    {
        // Write a session file with expired timestamp manually
        $session = new FileSessionDriver($this->tempDir, lifetime: 10);
        $session->start();
        $session->set('key', 'value');
        $session->save();

        $id = $session->getId();

        // Overwrite file with expired timestamp
        $file = $this->tempDir . '/architect_session_' . $id . '.json';
        $expiredData = ['key' => 'value', '__expires' => time() - 3600];
        file_put_contents($file, json_encode($expiredData));

        $session2 = new FileSessionDriver($this->tempDir, lifetime: 10);
        $session2->setId($id);
        $session2->start();

        $this->assertNull($session2->get('key'));
    }

    public function testDestroyDeletesFile(): void
    {
        $session = new FileSessionDriver($this->tempDir);
        $session->start();
        $session->set('key', 'value');
        $session->save();

        $id = $session->getId();
        $this->assertFileExists($this->tempDir . '/architect_session_' . $id . '.json');

        $session->destroy();
        $this->assertFileDoesNotExist($this->tempDir . '/architect_session_' . $id . '.json');
    }

    public function testRegenerateChangesId(): void
    {
        $session = new FileSessionDriver($this->tempDir);
        $session->start();
        $oldId = $session->getId();

        $session->regenerate();
        $this->assertNotSame($oldId, $session->getId());
    }

    public function testSetName(): void
    {
        $session = new FileSessionDriver($this->tempDir);
        $session->setName('my_session');
        $this->assertSame('my_session', $session->getName());
        $session->start();
        $session->set('key', 'value');
        $session->save();

        $id = $session->getId();
        $this->assertFileExists($this->tempDir . '/my_session_' . $id . '.json');
    }

    public function testMeta(): void
    {
        $session = new FileSessionDriver($this->tempDir, lifetime: 3600);
        $session->setName('test');
        $session->start();

        $meta = $session->meta();
        $this->assertSame('test', $meta['name']);
        $this->assertSame(3600, $meta['lifetime']);
        $this->assertTrue($meta['active']);
    }

    public function testSaveWithoutStartReturnsFalse(): void
    {
        $session = new FileSessionDriver($this->tempDir);
        $this->assertFalse($session->save());
    }
}
