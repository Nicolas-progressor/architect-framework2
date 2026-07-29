<?php

declare(strict_types=1);

namespace Tests\Services\Routing;

use Architect\Services\Routing\RouteGroup;
use PHPUnit\Framework\TestCase;

class RouteGroupTest extends TestCase
{
    public function testEmptyGroup(): void
    {
        $group = new RouteGroup();
        $this->assertSame('', $group->getPrefix());
        $this->assertSame([], $group->getMiddleware());
        $this->assertSame('', $group->getNamespace());
        $this->assertTrue($group->isEmpty());
        $this->assertCount(0, $group->getRoutes());
    }

    public function testGroupWithPrefix(): void
    {
        $group = new RouteGroup('admin');
        $this->assertSame('admin', $group->getPrefix());
    }

    public function testGroupWithMiddlewareString(): void
    {
        $group = new RouteGroup('admin', ['middleware' => 'auth']);
        $this->assertSame(['auth'], $group->getMiddleware());
    }

    public function testGroupWithMiddlewareArray(): void
    {
        $group = new RouteGroup('api', ['middleware' => ['auth', 'throttle']]);
        $this->assertSame(['auth', 'throttle'], $group->getMiddleware());
    }

    public function testGroupWithNamespace(): void
    {
        $group = new RouteGroup('admin', ['namespace' => 'Admin']);
        $this->assertSame('Admin', $group->getNamespace());
    }

    public function testAddRoute(): void
    {
        $group = new RouteGroup('admin');
        $group->addRoute('dashboard', [
            'module' => 'admin',
            'controller' => 'dashboard',
            'action' => 'index',
        ]);

        $routes = $group->getRoutes();
        $this->assertCount(1, $routes);
        $this->assertArrayHasKey('admin/dashboard', $routes);
        $this->assertFalse($group->isEmpty());
    }

    public function testAddRouteWithPrefix(): void
    {
        $group = new RouteGroup('api/v1');
        $group->addRoute('users', ['controller' => 'users']);

        $routes = $group->getRoutes();
        $this->assertArrayHasKey('api/v1/users', $routes);
    }

    public function testAddRouteWithEmptyPath(): void
    {
        $group = new RouteGroup('admin');
        $group->addRoute('', ['controller' => 'index']);

        $routes = $group->getRoutes();
        $this->assertArrayHasKey('admin', $routes);
    }

    public function testAddRouteWithName(): void
    {
        $group = new RouteGroup('admin');
        $group->addRoute('dashboard', ['controller' => 'dashboard'], 'admin.dashboard');

        $routes = $group->getRoutes();
        $key = array_key_first($routes);
        $this->assertSame('admin.dashboard', $routes[$key]['_name']);
    }

    public function testAddRouteInheritsMiddleware(): void
    {
        $group = new RouteGroup('admin', ['middleware' => 'auth']);
        $group->addRoute('dashboard', ['controller' => 'dashboard']);

        $routes = $group->getRoutes();
        $key = array_key_first($routes);
        $this->assertSame(['auth'], $routes[$key]['middleware']);
    }

    public function testAddRouteMergesMiddleware(): void
    {
        $group = new RouteGroup('admin', ['middleware' => ['auth', 'role']]);
        $group->addRoute('settings', ['controller' => 'settings'], '', ['middleware' => 'cache']);

        $routes = $group->getRoutes();
        $key = array_key_first($routes);
        $this->assertSame(['auth', 'role', 'cache'], $routes[$key]['middleware']);
    }

    public function testAddRouteInheritsNamespace(): void
    {
        $group = new RouteGroup('admin', ['namespace' => 'Admin']);
        $group->addRoute('dashboard', ['controller' => 'dashboard']);

        $routes = $group->getRoutes();
        $key = array_key_first($routes);
        $this->assertSame('Admin', $routes[$key]['namespace']);
    }

    public function testMultipleRoutes(): void
    {
        $group = new RouteGroup('admin');
        $group->addRoute('dashboard', ['controller' => 'dashboard']);
        $group->addRoute('settings', ['controller' => 'settings']);
        $group->addRoute('users', ['controller' => 'users']);

        $this->assertCount(3, $group->getRoutes());
    }

    public function testPrefixStripsSlashes(): void
    {
        $group = new RouteGroup('/admin/');
        $this->assertSame('admin', $group->getPrefix());
    }

    public function testFluentInterface(): void
    {
        $group = new RouteGroup('admin');
        $result = $group->addRoute('dashboard', ['controller' => 'dashboard']);
        $this->assertSame($group, $result);
    }

}
