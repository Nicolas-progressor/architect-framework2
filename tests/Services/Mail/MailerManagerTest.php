<?php

declare(strict_types=1);

namespace Tests\Services\Mail;

use Architect\Services\Mail\Drivers\LogMailer;
use Architect\Services\Mail\MailerManager;
use Architect\Services\Mail\Message;
use PHPUnit\Framework\TestCase;

class MailerManagerTest extends TestCase
{
    public function testDefaultDriver(): void
    {
        $manager = new MailerManager(['default' => 'log']);
        $this->assertSame('log', $manager->getDefaultDriver());
    }

    public function testDriverReturnsLogMailer(): void
    {
        $manager = new MailerManager(['default' => 'log']);
        $this->assertInstanceOf(LogMailer::class, $manager->driver());
    }

    public function testDriverByName(): void
    {
        $manager = new MailerManager(['default' => 'log']);
        $driver = $manager->driver('log');
        $this->assertInstanceOf(LogMailer::class, $driver);
    }

    public function testDriverIsCached(): void
    {
        $manager = new MailerManager(['default' => 'log']);
        $d1 = $manager->driver();
        $d2 = $manager->driver();
        $this->assertSame($d1, $d2);
    }

    public function testExtend(): void
    {
        $manager = new MailerManager(['default' => 'log']);
        $custom = new LogMailer();
        $manager->extend('custom', $custom);

        $this->assertSame($custom, $manager->driver('custom'));
    }

    public function testUnknownDriverThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $manager = new MailerManager([]);
        $manager->driver('unknown');
    }

    public function testSendViaManager(): void
    {
        $manager = new MailerManager(['default' => 'log']);
        $msg = Message::create()
            ->from('test@test.com')
            ->to('user@test.com')
            ->subject('Test')
            ->html('<p>Hello</p>');

        $result = $manager->send($msg);
        $this->assertTrue($result);

        /** @var LogMailer $driver */
        $driver = $manager->driver();
        $this->assertSame(1, $driver->count());
    }

    public function testSetDefaultDriver(): void
    {
        $manager = new MailerManager(['default' => 'log']);
        $manager->setDefaultDriver('log');
        $this->assertSame('log', $manager->getDefaultDriver());
    }

    public function testLogDriverConfig(): void
    {
        $manager = new MailerManager([
            'default' => 'log',
            'log' => ['log_path' => null],
        ]);

        $driver = $manager->driver('log');
        $this->assertInstanceOf(LogMailer::class, $driver);
    }
}
