<?php

declare(strict_types=1);

namespace Tests\Services\Mail;

use Architect\Services\Mail\Drivers\LogMailer;
use Architect\Services\Mail\Message;
use PHPUnit\Framework\TestCase;

class LogMailerTest extends TestCase
{
    private LogMailer $mailer;

    protected function setUp(): void
    {
        $this->mailer = new LogMailer();
    }

    public function testGetName(): void
    {
        $this->assertSame('log', $this->mailer->getName());
    }

    public function testIsAvailable(): void
    {
        $this->assertTrue($this->mailer->isAvailable());
    }

    public function testSendReturnsTrue(): void
    {
        $msg = Message::create()
            ->from('a@b.com')
            ->to('c@d.com')
            ->subject('Test')
            ->html('<p>Hello</p>');

        $this->assertTrue($this->mailer->send($msg));
    }

    public function testLogCapturesMessage(): void
    {
        $msg = Message::create()
            ->from('a@b.com')
            ->to('c@d.com')
            ->subject('Test')
            ->html('<p>Hello</p>');

        $this->mailer->send($msg);

        $this->assertCount(1, $this->mailer->getLog());
        $this->assertSame($msg, $this->mailer->last());
    }

    public function testCount(): void
    {
        $this->assertSame(0, $this->mailer->count());

        $this->mailer->send(Message::create()->to('a@a.com')->subject('1')->text(''));
        $this->mailer->send(Message::create()->to('b@b.com')->subject('2')->text(''));

        $this->assertSame(2, $this->mailer->count());
    }

    public function testReset(): void
    {
        $this->mailer->send(Message::create()->to('a@a.com')->subject('1')->text(''));
        $this->mailer->reset();
        $this->assertSame(0, $this->mailer->count());
    }

    public function testSendRaw(): void
    {
        $msg = Message::create()
            ->from('sender@test.com')
            ->to('recipient@test.com')
            ->subject('Test Subject')
            ->html('<h1>Hello</h1>');

        $result = $this->mailer->sendRaw($msg);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('From: sender@test.com', $result['output']);
        $this->assertStringContainsString('To: recipient@test.com', $result['output']);
        $this->assertStringContainsString('Subject: Test Subject', $result['output']);
        $this->assertStringContainsString('<h1>Hello</h1>', $result['output']);
    }

    public function testMultipleMessages(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->mailer->send(
                Message::create()->to('a@a.com')->subject("Msg {$i}")->text('')
            );
        }

        $this->assertCount(5, $this->mailer->getLog());
    }

    public function testLogToFile(): void
    {
        $logFile = sys_get_temp_dir() . '/architect_mail_test_' . uniqid() . '.log';
        $mailer = new LogMailer($logFile);

        $msg = Message::create()
            ->from('a@b.com')
            ->to('c@d.com')
            ->subject('File Log Test')
            ->text('Body content');

        $mailer->send($msg);

        $this->assertFileExists($logFile);
        $content = file_get_contents($logFile);
        $this->assertStringContainsString('File Log Test', $content);
        $this->assertStringContainsString('Body content', $content);

        unlink($logFile);
    }
}
