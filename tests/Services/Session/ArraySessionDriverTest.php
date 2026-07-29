<?php

declare(strict_types=1);

namespace Tests\Services\Session;

use Architect\Services\Session\Drivers\ArraySessionDriver;
use Architect\Services\Session\Drivers\FileSessionDriver;
use Architect\Services\Session\Drivers\CookieSessionDriver;
use PHPUnit\Framework\TestCase;

class ArraySessionDriverTest extends TestCase
{
    private ArraySessionDriver $session;

    protected function setUp(): void
    {
        ArraySessionDriver::resetCounter();
        $this->session = new ArraySessionDriver();
    }

    public function testStartReturnsTrue(): void
    {
        $this->assertTrue($this->session->start());
    }

    public function testIsActiveAfterStart(): void
    {
        $this->session->start();
        $this->assertTrue($this->session->isActive());
    }

    public function testIsNotActiveBeforeStart(): void
    {
        $this->assertFalse($this->session->isActive());
    }

    public function testStartIsIdempotent(): void
    {
        $this->session->start();
        $id1 = $this->session->getId();
        $this->session->start();
        $id2 = $this->session->getId();
        $this->assertSame($id1, $id2);
    }

    public function testSetAndGet(): void
    {
        $this->session->start();
        $this->session->set('name', 'John');
        $this->assertSame('John', $this->session->get('name'));
    }

    public function testGetDefault(): void
    {
        $this->session->start();
        $this->assertSame('default', $this->session->get('missing', 'default'));
    }

    public function testHas(): void
    {
        $this->session->start();
        $this->session->set('key', 'value');
        $this->assertTrue($this->session->has('key'));
        $this->assertFalse($this->session->has('missing'));
    }

    public function testHasNullValue(): void
    {
        $this->session->start();
        $this->session->set('key', null);
        $this->assertTrue($this->session->has('key'));
    }

    public function testRemove(): void
    {
        $this->session->start();
        $this->session->set('key', 'value');
        $this->session->remove('key');
        $this->assertFalse($this->session->has('key'));
    }

    public function testAll(): void
    {
        $this->session->start();
        $this->session->set('a', 1);
        $this->session->set('b', 2);
        $this->assertSame(['a' => 1, 'b' => 2], $this->session->all());
    }

    public function testPut(): void
    {
        $this->session->start();
        $this->session->put(['a' => 1, 'b' => 2]);
        $this->assertSame(1, $this->session->get('a'));
        $this->assertSame(2, $this->session->get('b'));
    }

    public function testPutOverwrites(): void
    {
        $this->session->start();
        $this->session->set('a', 'old');
        $this->session->put(['a' => 'new']);
        $this->assertSame('new', $this->session->get('a'));
    }

    public function testForget(): void
    {
        $this->session->start();
        $this->session->put(['a' => 1, 'b' => 2, 'c' => 3]);
        $this->session->forget(['a', 'c']);
        $this->assertSame(['b' => 2], $this->session->all());
    }

    public function testClear(): void
    {
        $this->session->start();
        $this->session->put(['a' => 1, 'b' => 2]);
        $this->session->clear();
        $this->assertSame([], $this->session->all());
    }

    public function testSetIdAndId(): void
    {
        $this->session->setId('custom-id');
        $this->session->start();
        $this->assertSame('custom-id', $this->session->getId());
    }

    public function testRegenerateClearsData(): void
    {
        $this->session->start();
        $this->session->set('key', 'value');
        $oldId = $this->session->getId();

        $this->session->regenerate(true);

        $this->assertNotSame($oldId, $this->session->getId());
        $this->assertNull($this->session->get('key'));
    }

    public function testRegenerateKeepsData(): void
    {
        $this->session->start();
        $this->session->set('key', 'value');
        $oldId = $this->session->getId();

        $this->session->regenerate(false);

        $this->assertNotSame($oldId, $this->session->getId());
        $this->assertSame('value', $this->session->get('key'));
    }

    public function testDestroy(): void
    {
        $this->session->start();
        $this->session->set('key', 'value');
        $this->session->destroy();
        $this->assertFalse($this->session->isActive());
        $this->assertSame([], $this->session->all());
    }

    public function testName(): void
    {
        $this->session->setName('custom');
        $this->assertSame('custom', $this->session->getName());
    }

    public function testLifetime(): void
    {
        $this->session->setLifetime(3600);
        $this->assertSame(3600, $this->session->getLifetime());
    }

    public function testSave(): void
    {
        $this->session->start();
        $this->assertTrue($this->session->save());
    }

    public function testMeta(): void
    {
        $this->session->setName('test');
        $this->session->setLifetime(1800);
        $this->session->start();

        $meta = $this->session->meta();
        $this->assertSame('test', $meta['name']);
        $this->assertSame(1800, $meta['lifetime']);
        $this->assertTrue($meta['active']);
        $this->assertNotEmpty($meta['id']);
    }

    public function testComplexValues(): void
    {
        $this->session->start();
        $this->session->set('array', [1, 2, 3]);
        $this->session->set('nested', ['a' => ['b' => 'c']]);
        $this->session->set('bool', true);
        $this->session->set('null', null);

        $this->assertSame([1, 2, 3], $this->session->get('array'));
        $this->assertSame(['a' => ['b' => 'c']], $this->session->get('nested'));
        $this->assertTrue($this->session->get('bool'));
        $this->assertNull($this->session->get('null'));
    }
}
