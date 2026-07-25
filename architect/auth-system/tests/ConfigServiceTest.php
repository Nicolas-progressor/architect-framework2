<?php

declare(strict_types=1);

namespace Architect\Auth\Tests;

use Architect\Auth\Services\ConfigService;
use PHPUnit\Framework\TestCase;

class ConfigServiceTest extends TestCase
{
    public function testDefaultConfig(): void
    {
        $service = new ConfigService();
        $config = $service->all();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('driver', $config);
        $this->assertEquals('database', $config['driver']);
        $this->assertArrayHasKey('roles', $config);
        $this->assertArrayHasKey('admin', $config['roles']);
    }

    public function testGet(): void
    {
        $service = new ConfigService();
        $this->assertEquals('database', $service->get('driver'));
        $this->assertEquals('guest', $service->get('default_role'));
        $this->assertEquals(1440, $service->get('session_lifetime'));
        $this->assertNull($service->get('nonexistent'));
        $this->assertEquals('default', $service->get('nonexistent', 'default'));
    }

    public function testGetRoles(): void
    {
        $service = new ConfigService();
        $roles = $service->getRoles();
        $this->assertArrayHasKey('admin', $roles);
        $this->assertArrayHasKey('guest', $roles);
    }

    public function testGetPermissions(): void
    {
        $service = new ConfigService();
        $permissions = $service->getPermissions();
        $this->assertArrayHasKey('view_public_content', $permissions);
        $this->assertEquals('Просмотр публичного контента', $permissions['view_public_content']);
    }
}