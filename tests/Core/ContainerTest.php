<?php

declare(strict_types=1);

namespace Tests\Core;

use Architect\Core\Container;
use Architect\Core\Exception\ContainerException;
use Architect\Core\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testSetAndGet(): void
    {
        $this->container->set('foo', 'bar');
        $this->assertSame('bar', $this->container->get('foo'));
    }

    public function testSetInstance(): void
    {
        $obj = new \stdClass();
        $obj->name = 'test';
        $this->container->set('obj', $obj);
        $this->assertSame($obj, $this->container->get('obj'));
    }

    public function testHas(): void
    {
        $this->assertFalse($this->container->has('missing'));
        $this->container->set('exists', 'value');
        $this->assertTrue($this->container->has('exists'));
    }

    public function testGetNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->container->get('nonexistent');
    }

    public function testFactory(): void
    {
        $callCount = 0;
        $this->container->factory('counter', function () use (&$callCount) {
            $callCount++;
            return new \stdClass();
        });

        $obj1 = $this->container->get('counter');
        $obj2 = $this->container->get('counter');
        $this->assertSame($obj1, $obj2, 'Factory should create singleton after first resolution');
        $this->assertSame(1, $callCount);
    }

    public function testBindStringClass(): void
    {
        $this->container->set('config', new class () {
            public array $config = ['db' => 'mysql'];
        });

        $this->container->bind('db', fn() => $this->container->get('config')->config['db']);
        $this->assertSame('mysql', $this->container->get('db'));
    }

    public function testBindCallable(): void
    {
        $this->container->bind('greeting', fn() => 'hello world');
        $this->assertSame('hello world', $this->container->get('greeting'));
    }

    public function testAlias(): void
    {
        $this->container->set('original', 'value');
        $this->container->alias('alias', 'original');
        $this->assertSame('value', $this->container->get('alias'));
    }

    public function testAfterResolving(): void
    {
        $called = false;
        $received = null;

        $this->container->afterResolving('test', function ($instance) use (&$called, &$received) {
            $called = true;
            $received = $instance;
        });

        $this->container->set('test', 'resolved_value');
        $this->container->get('test');

        $this->assertTrue($called);
        $this->assertSame('resolved_value', $received);
    }

    public function testAfterResolvingAlreadyResolved(): void
    {
        $called = false;
        $this->container->set('already', 'done');

        $this->container->afterResolving('already', function () use (&$called) {
            $called = true;
        });

        $this->assertTrue($called, 'Callback should fire immediately for already-resolved service');
    }

    public function testReset(): void
    {
        $this->container->set('foo', 'bar');
        $this->assertTrue($this->container->has('foo'));

        $this->container->reset();
        $this->assertFalse($this->container->has('foo'));
    }

    public function testResetKeepsBindings(): void
    {
        $this->container->bind('greeting', fn() => 'hello');
        $this->container->reset();
        $this->assertTrue($this->container->has('greeting'));
    }

    public function testSingleton(): void
    {
        $callCount = 0;
        $this->container->singleton('singleton', function () use (&$callCount) {
            $callCount++;
            return new \stdClass();
        });

        $obj1 = $this->container->get('singleton');
        $obj2 = $this->container->get('singleton');
        $this->assertSame($obj1, $obj2);
        $this->assertSame(1, $callCount);
    }

    public function testHasWithFactory(): void
    {
        $this->container->factory('factory_svc', fn() => new \stdClass());
        $this->assertTrue($this->container->has('factory_svc'));
    }

    public function testHasWithBind(): void
    {
        $this->container->bind('bind_svc', fn() => new \stdClass());
        $this->assertTrue($this->container->has('bind_svc'));
    }

    public function testHasWithLazy(): void
    {
        $this->container->lazy('lazy_svc', fn() => new \stdClass());
        $this->assertTrue($this->container->has('lazy_svc'));
    }

    public function testLazyService(): void
    {
        $callCount = 0;
        $this->container->lazy('lazy', function () use (&$callCount) {
            $callCount++;
            $obj = new \stdClass();
            $obj->value = 42;
            return $obj;
        });

        $this->assertSame(0, $callCount);
        $this->assertFalse($this->container->isLazyInitialized('lazy'));

        $result = $this->container->get('lazy');
        $this->assertSame(1, $callCount);
        $this->assertTrue($this->container->isLazyInitialized('lazy'));
        $this->assertSame(42, $result->value);
    }

    public function testLazyReset(): void
    {
        $callCount = 0;
        $this->container->lazy('lazy', function () use (&$callCount) {
            $callCount++;
            return new \stdClass();
        });

        $this->container->get('lazy');
        $this->assertSame(1, $callCount);

        $this->container->reset();
        $this->container->get('lazy');
        $this->assertSame(2, $callCount);
    }

    public function testIsLazyInitializedNotFound(): void
    {
        $this->assertFalse($this->container->isLazyInitialized('nonexistent'));
    }

    public function testBindStringClassName(): void
    {
        $this->container->set('config', new \stdClass());
        $this->container->set('config', (object)['db' => 'mysql']);

        $this->container->bind('db_config', \stdClass::class);
        $result = $this->container->get('db_config');
        $this->assertInstanceOf(\stdClass::class, $result);
    }

    public function testMultipleAfterResolving(): void
    {
        $calls = [];
        $this->container->afterResolving('multi', function ($v) use (&$calls) { $calls[] = 'a'; });
        $this->container->afterResolving('multi', function ($v) use (&$calls) { $calls[] = 'b'; });

        $this->container->set('multi', 'value');
        $this->container->get('multi');

        $this->assertSame(['a', 'b'], $calls);
    }

    public function testContainerException(): void
    {
        $exception = new ContainerException('test error');
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(\Psr\Container\ContainerExceptionInterface::class, $exception);
    }

    public function testNotFoundException(): void
    {
        $exception = new NotFoundException('not found');
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(\Psr\Container\NotFoundExceptionInterface::class, $exception);
    }
}
