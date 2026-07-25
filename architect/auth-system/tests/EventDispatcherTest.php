<?php

declare(strict_types=1);

namespace Architect\AuthSystem\Tests;

use Architect\AuthSystem\Events\SimpleEventDispatcher;
use PHPUnit\Framework\TestCase;

class EventDispatcherTest extends TestCase
{
    private SimpleEventDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = new SimpleEventDispatcher();
    }

    public function testSubscribeAndDispatch(): void
    {
        $called = false;
        $this->dispatcher->subscribe('test.event', function () use (&$called) {
            $called = true;
        });

        $this->dispatcher->dispatch('test.event');
        $this->assertTrue($called);
    }

    public function testDispatchWithPayload(): void
    {
        $receivedPayload = null;
        $this->dispatcher->subscribe('test.payload', function ($payload) use (&$receivedPayload) {
            $receivedPayload = $payload;
        });

        $expected = ['data' => 123];
        $this->dispatcher->dispatch('test.payload', $expected);
        $this->assertSame($expected, $receivedPayload);
    }

    public function testPriority(): void
    {
        $order = [];
        $this->dispatcher->subscribe('test.order', function () use (&$order) {
            $order[] = 'low';
        }, 0);
        $this->dispatcher->subscribe('test.order', function () use (&$order) {
            $order[] = 'high';
        }, 10);

        $this->dispatcher->dispatch('test.order');
        $this->assertSame(['high', 'low'], $order);
    }

    public function testUnsubscribe(): void
    {
        $called = false;
        $listener = function () use (&$called) {
            $called = true;
        };

        $this->dispatcher->subscribe('test.unsub', $listener);
        $this->dispatcher->unsubscribe('test.unsub', $listener);

        $this->dispatcher->dispatch('test.unsub');
        $this->assertFalse($called);
    }

    public function testHasListeners(): void
    {
        $this->assertFalse($this->dispatcher->hasListeners('test.empty'));
        $this->dispatcher->subscribe('test.empty', function () {});
        $this->assertTrue($this->dispatcher->hasListeners('test.empty'));
    }

    public function testGetListeners(): void
    {
        $listener = function () {};
        $this->dispatcher->subscribe('test.get', $listener);
        $listeners = $this->dispatcher->getListeners('test.get');
        $this->assertCount(1, $listeners);
        $this->assertSame($listener, $listeners[0]['listener']);
    }
}