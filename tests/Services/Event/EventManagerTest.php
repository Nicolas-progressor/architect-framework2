<?php

declare(strict_types=1);

namespace Tests\Services\Event;

use Architect\Services\Event\Event;
use Architect\Services\Event\EventManager;
use PHPUnit\Framework\TestCase;

class EventManagerTest extends TestCase
{
    private EventManager $manager;

    protected function setUp(): void
    {
        $this->manager = new EventManager();
    }

    public function testListenAndDispatch(): void
    {
        $result = [];
        $this->manager->listen('user.registered', function (Event $e) use (&$result) {
            $result[] = $e->getPayload();
        });

        $this->manager->dispatch('user.registered', ['id' => 1]);

        $this->assertCount(1, $result);
        $this->assertSame(['id' => 1], $result[0]);
    }

    public function testHasListeners(): void
    {
        $this->assertFalse($this->manager->hasListeners('test.event'));

        $this->manager->listen('test.event', fn() => null);

        $this->assertTrue($this->manager->hasListeners('test.event'));
    }

    public function testMultipleListeners(): void
    {
        $order = [];
        $this->manager->listen('test', function () use (&$order) { $order[] = 'a'; });
        $this->manager->listen('test', function () use (&$order) { $order[] = 'b'; });

        $this->manager->dispatch('test');

        $this->assertCount(2, $order);
    }

    public function testPriorityOrder(): void
    {
        $order = [];
        $this->manager->listen('test', function () use (&$order) { $order[] = 'low'; }, -10);
        $this->manager->listen('test', function () use (&$order) { $order[] = 'high'; }, 10);
        $this->manager->listen('test', function () use (&$order) { $order[] = 'normal'; }, 0);

        $this->manager->dispatch('test');

        $this->assertSame(['high', 'normal', 'low'], $order);
    }

    public function testStopPropagation(): void
    {
        $order = [];
        $this->manager->listen('test', function (Event $e) use (&$order) {
            $order[] = 'first';
            $e->stopPropagation();
        });
        $this->manager->listen('test', function () use (&$order) {
            $order[] = 'second';
        });

        $this->manager->dispatch('test');

        $this->assertSame(['first'], $order);
    }

    public function testWildcardPattern(): void
    {
        $result = [];
        $this->manager->listen('user.*', function (Event $e) use (&$result) {
            $result[] = $e->getName();
        });

        $this->manager->dispatch('user.registered');
        $this->manager->dispatch('user.deleted');
        $this->manager->dispatch('post.created');

        $this->assertCount(2, $result);
        $this->assertSame(['user.registered', 'user.deleted'], $result);
    }

    public function testWildcardAll(): void
    {
        $count = 0;
        $this->manager->listen('*', function () use (&$count) { $count++; });

        $this->manager->dispatch('one');
        $this->manager->dispatch('two');
        $this->manager->dispatch('three');

        $this->assertSame(3, $count);
    }

    public function testUnsubscribe(): void
    {
        $count = 0;
        $listener = function () use (&$count) { $count++; };

        $this->manager->listen('test', $listener);
        $this->manager->dispatch('test');
        $this->assertSame(1, $count);

        $this->manager->unsubscribe('test', $listener);
        $this->manager->dispatch('test');
        $this->assertSame(1, $count);
    }

    public function testUnsubscribeWildcard(): void
    {
        $count = 0;
        $listener = function () use (&$count) { $count++; };

        $this->manager->listen('user.*', $listener);
        $this->manager->dispatch('user.one');
        $this->assertSame(1, $count);

        $this->manager->unsubscribe('user.*', $listener);
        $this->manager->dispatch('user.two');
        $this->assertSame(1, $count);
    }

    public function testClearListeners(): void
    {
        $this->manager->listen('test', fn() => null);
        $this->manager->listen('other', fn() => null);
        $this->assertTrue($this->manager->hasListeners('test'));

        $this->manager->clearListeners('test');
        $this->assertFalse($this->manager->hasListeners('test'));
        $this->assertTrue($this->manager->hasListeners('other'));
    }

    public function testClearAllListeners(): void
    {
        $this->manager->listen('a', fn() => null);
        $this->manager->listen('b', fn() => null);
        $this->manager->listen('c.*', fn() => null);

        $this->manager->clearListeners();

        $this->assertEmpty($this->manager->getRegisteredEvents());
    }

    public function testGetRegisteredEvents(): void
    {
        $this->manager->listen('a', fn() => null);
        $this->manager->listen('b', fn() => null);
        $this->manager->listen('c.*', fn() => null);

        $events = $this->manager->getRegisteredEvents();
        $this->assertCount(3, $events);
        $this->assertContains('a', $events);
        $this->assertContains('b', $events);
        $this->assertContains('c.*', $events);
    }

    public function testDispatchWithEventObject(): void
    {
        $result = null;
        $this->manager->listen('custom', function (Event $e) use (&$result) {
            $result = $e->getPayload();
        });

        $event = new Event('custom', ['key' => 'value']);
        $this->manager->dispatch('custom', $event);

        $this->assertSame(['key' => 'value'], $result);
    }

    public function testHasListenersWithWildcard(): void
    {
        $this->manager->listen('auth.*', fn() => null);

        $this->assertTrue($this->manager->hasListeners('auth.login'));
        $this->assertTrue($this->manager->hasListeners('auth.logout'));
        $this->assertFalse($this->manager->hasListeners('user.created'));
    }

    public function testEventName(): void
    {
        $event = new Event('test.event', 'data');
        $this->assertSame('test.event', $event->getName());
    }

    public function testEventPayloadDefaultNull(): void
    {
        $event = new Event('test');
        $this->assertNull($event->getPayload());
    }

    public function testAddFilterAndApply(): void
    {
        $this->manager->addFilter('format.name', function ($value) {
            return strtoupper($value);
        });

        $result = $this->manager->applyFilters('format.name', 'hello');
        $this->assertSame('HELLO', $result);
    }

    public function testFilterChain(): void
    {
        $this->manager->addFilter('filter.num', fn($v) => $v * 2);
        $this->manager->addFilter('filter.num', fn($v) => $v + 1);

        $result = $this->manager->applyFilters('filter.num', 5);
        $this->assertSame(11, $result);
    }

    public function testFilterPriority(): void
    {
        $this->manager->addFilter('filter.p', fn($v) => $v . '-low', 5);
        $this->manager->addFilter('filter.p', fn($v) => $v . '-high', 20);

        $result = $this->manager->applyFilters('filter.p', 'start');
        $this->assertSame('start-high-low', $result);
    }

    public function testHasFilter(): void
    {
        $this->assertFalse($this->manager->hasFilter('test.filter'));

        $this->manager->addFilter('test.filter', fn($v) => $v);

        $this->assertTrue($this->manager->hasFilter('test.filter'));
    }

    public function testRemoveFilter(): void
    {
        $fn = fn($v) => $v . '-modified';

        $this->manager->addFilter('test', $fn);
        $this->assertTrue($this->manager->hasFilter('test'));

        $this->manager->removeFilter('test', $fn);
        $this->assertFalse($this->manager->hasFilter('test'));
    }

    public function testFilterWithExtraArgs(): void
    {
        $this->manager->addFilter('greet', function ($greeting, $name) {
            return $greeting . ', ' . $name . '!';
        });

        $result = $this->manager->applyFilters('greet', 'Hello', 'World');
        $this->assertSame('Hello, World!', $result);
    }

    public function testFilterWildcard(): void
    {
        $this->manager->addFilter('app.*', fn($v) => $v . '-filtered');

        $result = $this->manager->applyFilters('app.title', 'Test');
        $this->assertSame('Test-filtered', $result);
    }

    public function testListenersAndFiltersAreSeparate(): void
    {
        $actionCalled = false;
        $this->manager->listen('hook', function () use (&$actionCalled) {
            $actionCalled = true;
        });

        $this->manager->addFilter('hook', fn($v) => $v . '-filtered');

        $filterResult = $this->manager->applyFilters('hook', 'value');
        $this->assertFalse($actionCalled, 'Listeners should not run during filter');
        $this->assertSame('value-filtered', $filterResult);
    }

    public function testDispatchReturnsModifiedPayload(): void
    {
        $this->manager->listen('modify', function (Event $e) {
            $data = $e->getPayload();
            $data['modified'] = true;
            $e->setPayload($data);
        });

        $result = $this->manager->dispatch('modify', ['original' => true]);

        $this->assertSame(['original' => true, 'modified' => true], $result);
    }

    public function testMultipleWildcardPatterns(): void
    {
        $events = [];
        $this->manager->listen('user.*', function (Event $e) use (&$events) { $events[] = 'user'; });
        $this->manager->listen('*.created', function (Event $e) use (&$events) { $events[] = 'created'; });
        $this->manager->listen('*', function (Event $e) use (&$events) { $events[] = 'all'; });

        $this->manager->dispatch('user.created');

        $this->assertContains('user', $events);
        $this->assertContains('all', $events);
    }
}
