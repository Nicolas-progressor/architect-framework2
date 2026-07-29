<?php

declare(strict_types=1);

namespace Tests\Services\Routing;

use Architect\Services\App\Contracts\AppsServiceInterface;
use Architect\Services\Config\Contracts\ConfigInterface;
use Architect\Services\Routing\Contracts\FileSystemInterface;
use Architect\Services\Routing\Contracts\RequestInterface;
use Architect\Services\Routing\Contracts\RouteLoaderInterface;
use Architect\Services\Routing\ModuleResolver;
use Architect\Services\Routing\Router;
use Architect\Contracts\Core\ContainerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class RouterFeaturesTest extends TestCase
{
    private Router $router;
    private MockObject $request;
    private MockObject $apps;

    protected function setUp(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $this->request = $this->createMock(RequestInterface::class);
        $this->request->method('getPath')->willReturn('/');
        $this->request->method('getSegments')->willReturn([]);

        $loader = $this->createMock(RouteLoaderInterface::class);
        $loader->method('loadDirectory')->willReturn([]);
        $loader->method('load')->willReturn([]);

        $fs = $this->createMock(FileSystemInterface::class);
        $fs->method('exists')->willReturn(false);
        $fs->method('isDir')->willReturn(false);

        $this->apps = $this->createMock(AppsServiceInterface::class);
        $this->apps->method('getDefaultRoute')->willReturn([
            'module' => 'home',
            'controller' => 'home',
            'action' => 'index',
        ]);
        $this->apps->method('hasApp')->willReturn(false);
        $this->apps->method('getAppDir')->willReturn('/tmp/');

        $moduleResolver = new ModuleResolver($this->apps, $fs);

        $config = $this->createMock(ConfigInterface::class);
        $config->method('get')->willReturn([]);

        $this->router = new Router(
            $container,
            $this->request,
            $loader,
            $moduleResolver,
            $config,
            $this->apps,
            $fs,
        );
    }

    // === Named Routes ===

    public function testRegisterNamedRoute(): void
    {
        $this->router->name('login', 'login', [
            'controller' => 'auth',
            'action' => 'login',
        ]);

        $this->assertTrue($this->router->hasNamedRoute('login'));
        $this->assertFalse($this->router->hasNamedRoute('logout'));
    }

    public function testGenerateUrlFromNamedRoute(): void
    {
        $this->router->name('users.show', 'users/{id}', [
            'controller' => 'users',
            'action' => 'show',
        ]);

        $url = $this->router->route('users.show', ['id' => '42']);
        $this->assertSame('/users/42', $url);
    }

    public function testGenerateUrlMultipleParams(): void
    {
        $this->router->name('posts.show', 'users/{userId}/posts/{postId}', [
            'controller' => 'posts',
            'action' => 'show',
        ]);

        $url = $this->router->route('posts.show', ['userId' => '5', 'postId' => '99']);
        $this->assertSame('/users/5/posts/99', $url);
    }

    public function testGenerateUrlNoParams(): void
    {
        $this->router->name('home', 'home', [
            'controller' => 'home',
        ]);

        $url = $this->router->route('home');
        $this->assertSame('/home', $url);
    }

    public function testGenerateUrlUnknownRoute(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Route 'unknown' is not registered.");
        $this->router->route('unknown');
    }

    public function testGetNamedRoutes(): void
    {
        $this->router->name('login', 'login', ['controller' => 'auth']);
        $this->router->name('dashboard', 'dashboard', ['controller' => 'dashboard']);

        $named = $this->router->getNamedRoutes();
        $this->assertCount(2, $named);
        $this->assertArrayHasKey('login', $named);
        $this->assertArrayHasKey('dashboard', $named);
    }

    public function testNamedRouteIsAlsoInRoutes(): void
    {
        $this->router->name('test', 'test-path', ['controller' => 'test']);

        $routes = $this->router->routes;
        $this->assertArrayHasKey('test-path', $routes);
        $this->assertSame('test', $routes['test-path']['_name']);
    }

    public function testFluentNamedRoute(): void
    {
        $result = $this->router->name('a', 'path-a', ['controller' => 'a']);
        $this->assertSame($this->router, $result);
    }

    // === Parameter Binding ===

    public function testMatchPatternExact(): void
    {
        $result = $this->router->matchPattern('users', 'users');
        $this->assertSame([], $result);
    }

    public function testMatchPatternWithParam(): void
    {
        $result = $this->router->matchPattern('users/{id}', 'users/42');
        $this->assertSame(['id' => '42'], $result);
    }

    public function testMatchPatternMultipleParams(): void
    {
        $result = $this->router->matchPattern('users/{userId}/posts/{postId}', 'users/5/posts/99');
        $this->assertSame(['userId' => '5', 'postId' => '99'], $result);
    }

    public function testMatchPatternNoMatch(): void
    {
        $result = $this->router->matchPattern('users/{id}', 'posts/42');
        $this->assertNull($result);
    }

    public function testMatchPatternPartialNoMatch(): void
    {
        $result = $this->router->matchPattern('users/{id}', 'users/42/extra');
        $this->assertNull($result);
    }

    public function testMatchPatternEmptyPath(): void
    {
        $result = $this->router->matchPattern('users/{id}', '');
        $this->assertNull($result);
    }

    // === Route Groups ===

    public function testGroupAddsPrefix(): void
    {
        $this->router->group('admin', [], function (Router $r) {
            $r->name('dashboard', 'dashboard', ['controller' => 'dashboard']);
        });

        $named = $this->router->getNamedRoutes();
        $this->assertArrayHasKey('dashboard', $named);
        $this->assertSame('admin/dashboard', $named['dashboard']['path']);
    }

    public function testGroupNestedPrefix(): void
    {
        $this->router->group('api', [], function (Router $r) {
            $r->group('v1', [], function (Router $r2) {
                $r2->name('users', 'users', ['controller' => 'users']);
            });
        });

        $named = $this->router->getNamedRoutes();
        $this->assertSame('api/v1/users', $named['users']['path']);
    }

    public function testGroupMiddleware(): void
    {
        $this->router->group('admin', ['middleware' => 'auth'], function (Router $r) {
            $r->routeMiddleware('dashboard', ['controller' => 'dashboard'], ['cache']);
        });

        $route = $this->router->routes['admin/dashboard'] ?? null;
        $this->assertNotNull($route);
        $this->assertContains('auth', $route['middleware']);
        $this->assertContains('cache', $route['middleware']);
    }

    public function testGroupNamespace(): void
    {
        $this->router->group('admin', ['namespace' => 'Admin'], function (Router $r) {
            $r->name('dashboard', 'dashboard', ['controller' => 'dashboard']);
        });

        $named = $this->router->getNamedRoutes();
        $this->assertSame('Admin', $named['dashboard']['route']['namespace']);
    }

    public function testGroupMiddlewareMerge(): void
    {
        $this->router->group('admin', ['middleware' => 'auth'], function (Router $r) {
            $r->routeMiddleware('dashboard', ['controller' => 'dashboard'], ['cache']);
        });

        $route = $this->router->routes['admin/dashboard'] ?? null;
        $this->assertNotNull($route);
        $this->assertContains('auth', $route['middleware']);
        $this->assertContains('cache', $route['middleware']);
    }

    // === Per-Route Middleware ===

    public function testRouteMiddleware(): void
    {
        $this->router->routeMiddleware('admin/settings', [
            'controller' => 'settings',
        ], ['auth', 'admin']);

        $route = $this->router->routes['admin/settings'] ?? null;
        $this->assertNotNull($route);
        $this->assertSame(['auth', 'admin'], $route['middleware']);
    }

    public function testRouteMiddlewareEmpty(): void
    {
        $this->router->routeMiddleware('about', [
            'controller' => 'about',
        ]);

        $route = $this->router->routes['about'] ?? null;
        $this->assertNotNull($route);
        $this->assertSame([], $route['middleware']);
    }

    public function testGetRouteMiddlewareEmpty(): void
    {
        $this->assertSame([], $this->router->getRouteMiddleware());
    }
}
