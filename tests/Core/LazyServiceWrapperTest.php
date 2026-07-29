<?php

declare(strict_types=1);

namespace Tests\Core;

use Architect\Core\LazyServiceWrapper;
use PHPUnit\Framework\TestCase;

class LazyServiceWrapperTest extends TestCase
{
    public function testLazyInitialization(): void
    {
        $wrapper = new LazyServiceWrapper(function () {
            $obj = new \stdClass();
            $obj->value = 42;
            return $obj;
        });

        $this->assertFalse($wrapper->isInitialized());

        $instance = $wrapper->getInstance();
        $this->assertTrue($wrapper->isInitialized());
        $this->assertSame(42, $instance->value);
    }

    public function testSingletonBehavior(): void
    {
        $callCount = 0;
        $wrapper = new LazyServiceWrapper(function () use (&$callCount) {
            $callCount++;
            return new \stdClass();
        });

        $obj1 = $wrapper->getInstance();
        $obj2 = $wrapper->getInstance();
        $this->assertSame($obj1, $obj2);
        $this->assertSame(1, $callCount);
    }

    public function testReset(): void
    {
        $callCount = 0;
        $wrapper = new LazyServiceWrapper(function () use (&$callCount) {
            $callCount++;
            return new \stdClass();
        });

        $wrapper->getInstance();
        $this->assertSame(1, $callCount);

        $wrapper->reset();
        $this->assertFalse($wrapper->isInitialized());

        $wrapper->getInstance();
        $this->assertSame(2, $callCount);
    }

    public function testProxyCall(): void
    {
        $obj = new \stdClass();
        $obj->name = 'test';

        $wrapper = new LazyServiceWrapper(fn() => $obj);
        $result = $wrapper->getInstance();

        $this->assertSame('test', $result->name);
    }

    public function testMagicGet(): void
    {
        $obj = new \stdClass();
        $obj->value = 99;

        $wrapper = new LazyServiceWrapper(fn() => $obj);
        $this->assertSame(99, $wrapper->value);
    }

    public function testMagicSet(): void
    {
        $obj = new \stdClass();
        $obj->value = 0;

        $wrapper = new LazyServiceWrapper(fn() => $obj);
        $wrapper->value = 50;
        $this->assertSame(50, $obj->value);
    }

    public function testMagicIsset(): void
    {
        $obj = new \stdClass();
        $obj->exists = true;

        $wrapper = new LazyServiceWrapper(fn() => $obj);
        $this->assertTrue(isset($wrapper->exists));
        $this->assertFalse(isset($wrapper->missing));
    }

    public function testFactoryReceivesContainer(): void
    {
        $receivedContainer = null;
        $container = new \Architect\Core\Container();

        $wrapper = new LazyServiceWrapper(function ($c) use (&$receivedContainer) {
            $receivedContainer = $c;
            return new \stdClass();
        }, $container);

        $wrapper->getInstance();
        $this->assertSame($container, $receivedContainer);
    }
}
