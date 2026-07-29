<?php

declare(strict_types=1);

namespace Tests\Core;

use Architect\Core\Statement;
use Architect\Contracts\Core\ContainerInterface;
use PHPUnit\Framework\TestCase;

class StatementTest extends TestCase
{
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $debug = new class () {
            public function startStage(string $name): void {}
            public function endStage(): void {}
        };
        $this->container->method('get')->willReturn($debug);
    }

    public function testGetStatements(): void
    {
        $statement = new Statement($this->container);
        $statements = $statement->getStatements();
        $this->assertContains('core_init', $statements);
        $this->assertContains('app_load', $statements);
        $this->assertCount(8, $statements);
    }

    public function testOnAndRun(): void
    {
        $called = false;
        $statement = new Statement($this->container);

        $statement->on('core_init', function () use (&$called) {
            $called = true;
        });

        $statement->run('core_init');
        $this->assertTrue($called);
    }

    public function testIsExecuted(): void
    {
        $statement = new Statement($this->container);
        $this->assertFalse($statement->isExecuted('core_init'));

        $statement->run('core_init');
        $this->assertTrue($statement->isExecuted('core_init'));
    }

    public function testRunNoHooks(): void
    {
        $statement = new Statement($this->container);
        $statement->run('nonexistent');
        $this->assertFalse($statement->isExecuted('nonexistent'));
    }

    public function testCustomStatement(): void
    {
        $called = false;
        $statement = new Statement($this->container);

        $statement->on('custom.hook', function () use (&$called) {
            $called = true;
        });

        $statement->run('custom.hook');
        $this->assertTrue($called);
        $this->assertTrue($statement->isExecuted('custom.hook'));
    }

    public function testPriority(): void
    {
        $order = [];
        $statement = new Statement($this->container);

        $statement->on('test.priority', function () use (&$order) {
            $order[] = 'low';
        }, 20);
        $statement->on('test.priority', function () use (&$order) {
            $order[] = 'high';
        }, 1);

        $statement->run('test.priority');
        $this->assertSame(['high', 'low'], $order);
    }

    public function testMultipleCallbacks(): void
    {
        $calls = [];
        $statement = new Statement($this->container);

        $statement->on('multi', function () use (&$calls) { $calls[] = 'a'; });
        $statement->on('multi', function () use (&$calls) { $calls[] = 'b'; });
        $statement->on('multi', function () use (&$calls) { $calls[] = 'c'; });

        $statement->run('multi');
        $this->assertSame(['a', 'b', 'c'], $calls);
    }
}
