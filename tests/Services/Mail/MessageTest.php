<?php

declare(strict_types=1);

namespace Tests\Services\Mail;

use Architect\Services\Mail\Message;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    public function testCreate(): void
    {
        $msg = Message::create();
        $this->assertInstanceOf(Message::class, $msg);
    }

    public function testFrom(): void
    {
        $msg = Message::create()->from('sender@example.com', 'Sender');
        $this->assertSame(['sender@example.com' => 'Sender'], $msg->getFrom());
    }

    public function testTo(): void
    {
        $msg = Message::create()->to('user@example.com', 'User');
        $this->assertSame(['user@example.com' => 'User'], $msg->getTo());
    }

    public function testMultipleTo(): void
    {
        $msg = Message::create()
            ->to('a@example.com', 'A')
            ->to('b@example.com', 'B');

        $this->assertCount(2, $msg->getTo());
        $this->assertArrayHasKey('a@example.com', $msg->getTo());
        $this->assertArrayHasKey('b@example.com', $msg->getTo());
    }

    public function testCc(): void
    {
        $msg = Message::create()->cc('cc@example.com');
        $this->assertSame(['cc@example.com' => ''], $msg->getCc());
    }

    public function testBcc(): void
    {
        $msg = Message::create()->bcc('bcc@example.com');
        $this->assertSame(['bcc@example.com' => ''], $msg->getBcc());
    }

    public function testSubject(): void
    {
        $msg = Message::create()->subject('Hello');
        $this->assertSame('Hello', $msg->getSubject());
    }

    public function testHtml(): void
    {
        $msg = Message::create()->html('<h1>Hello</h1>');
        $this->assertSame('<h1>Hello</h1>', $msg->getHtmlBody());
        $this->assertSame('<h1>Hello</h1>', $msg->getBody());
        $this->assertTrue($msg->isHtml());
    }

    public function testText(): void
    {
        $msg = Message::create()->text('Hello');
        $this->assertSame('Hello', $msg->getTextBody());
        $this->assertSame('Hello', $msg->getBody());
        $this->assertFalse($msg->isHtml());
    }

    public function testBody(): void
    {
        $htmlMsg = Message::create()->body('<p>Hi</p>', true);
        $this->assertTrue($htmlMsg->isHtml());
        $this->assertSame('<p>Hi</p>', $htmlMsg->getHtmlBody());

        $textMsg = Message::create()->body('Hi', false);
        $this->assertFalse($textMsg->isHtml());
        $this->assertSame('Hi', $textMsg->getTextBody());
    }

    public function testHeader(): void
    {
        $msg = Message::create()->header('X-Custom', 'value');
        $this->assertSame(['X-Custom' => 'value'], $msg->getHeaders());
    }

    public function testFluentInterface(): void
    {
        $msg = Message::create()
            ->from('a@a.com')
            ->to('b@b.com')
            ->subject('Test')
            ->html('<p>Test</p>')
            ->header('X-Test', '1');

        $this->assertSame('a@a.com', array_key_first($msg->getFrom()));
        $this->assertSame('b@b.com', array_key_first($msg->getTo()));
        $this->assertSame('Test', $msg->getSubject());
    }

    public function testFormatAddressWithName(): void
    {
        $result = Message::formatAddress('test@example.com', 'Test User');
        $this->assertSame('"Test User" <test@example.com>', $result);
    }

    public function testFormatAddressWithoutName(): void
    {
        $result = Message::formatAddress('test@example.com');
        $this->assertSame('test@example.com', $result);
    }

    public function testAttach(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'mail_');
        file_put_contents($tempFile, 'content');

        $msg = Message::create()->attach($tempFile, 'file.txt');
        $attachments = $msg->getAttachments();

        $this->assertCount(1, $attachments);
        $this->assertSame('file.txt', $attachments[0]['name']);
        $this->assertArrayHasKey('mimeType', $attachments[0]);

        unlink($tempFile);
    }
}
