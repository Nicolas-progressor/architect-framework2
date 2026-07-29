<?php

declare(strict_types=1);

namespace Tests\Services\Session;

use Architect\Services\Session\Drivers\ArraySessionDriver;
use Architect\Services\Session\Drivers\CookieSessionDriver;
use Architect\Services\Session\Drivers\FileSessionDriver;
use Architect\Services\Session\SessionManager;
use PHPUnit\Framework\TestCase;

class SessionManagerTest extends TestCase
{
    public function testDefaultDriver(): void
    {
        $manager = new SessionManager(['default' => 'array']);
        $this->assertSame('array', $manager->getDefaultDriver());
    }

    public function testDriverReturnsInstance(): void
    {
        $manager = new SessionManager(['default' => 'array']);
        $driver = $manager->driver();
        $this->assertInstanceOf(ArraySessionDriver::class, $driver);
    }

    public function testDriverByName(): void
    {
        $manager = new SessionManager(['default' => 'array']);
        $file = $manager->driver('file');
        $this->assertInstanceOf(FileSessionDriver::class, $file);
    }

    public function testDriverIsCached(): void
    {
        $manager = new SessionManager(['default' => 'array']);
        $d1 = $manager->driver();
        $d2 = $manager->driver();
        $this->assertSame($d1, $d2);
    }

    public function testExtend(): void
    {
        $manager = new SessionManager(['default' => 'array']);
        $custom = new ArraySessionDriver();
        $manager->extend('custom', $custom);

        $this->assertSame($custom, $manager->driver('custom'));
    }

    public function testExtendWith(): void
    {
        $manager = new SessionManager(['default' => 'array']);
        $manager->extendWith('custom', fn() => new ArraySessionDriver());

        $this->assertInstanceOf(ArraySessionDriver::class, $manager->driver('custom'));
    }

    public function testUnknownDriverThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $manager = new SessionManager([]);
        $manager->driver('unknown');
    }

    public function testGetDrivers(): void
    {
        $manager = new SessionManager(['default' => 'array']);
        $manager->driver('array');
        $manager->driver('file');

        $drivers = $manager->getDrivers();
        $this->assertContains('array', $drivers);
        $this->assertContains('file', $drivers);
    }

    public function testSetDefaultDriver(): void
    {
        $manager = new SessionManager(['default' => 'array']);
        $manager->setDefaultDriver('file');
        $this->assertSame('file', $manager->getDefaultDriver());
    }

    public function testFileDriverConfig(): void
    {
        $tempDir = sys_get_temp_dir();
        $manager = new SessionManager([
            'default' => 'file',
            'file' => ['storage_path' => $tempDir, 'lifetime' => 3600],
        ]);

        $driver = $manager->driver('file');
        $this->assertInstanceOf(FileSessionDriver::class, $driver);
        $this->assertSame(3600, $driver->getLifetime());
    }

    public function testCookieDriver(): void
    {
        $manager = new SessionManager([
            'default' => 'cookie',
            'cookie' => ['secret' => 'test-secret', 'lifetime' => 7200],
        ]);

        $driver = $manager->driver('cookie');
        $this->assertInstanceOf(CookieSessionDriver::class, $driver);
        $this->assertSame(7200, $driver->getLifetime());
    }

    public function testFullWorkflow(): void
    {
        $manager = new SessionManager(['default' => 'array']);
        $session = $manager->driver();

        $session->start();
        $session->set('user_id', 42);
        $session->set('cart', ['item1', 'item2']);
        $session->save();

        $this->assertSame(42, $session->get('user_id'));
        $this->assertSame(['item1', 'item2'], $session->get('cart'));
        $this->assertTrue($session->has('user_id'));

        $session->remove('cart');
        $this->assertNull($session->get('cart'));
        $this->assertSame(42, $session->get('user_id'));
    }
}
