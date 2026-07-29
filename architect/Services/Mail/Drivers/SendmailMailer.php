<?php

declare(strict_types=1);

namespace Architect\Services\Mail\Drivers;

use Architect\Services\Mail\Contracts\MailerInterface;
use Architect\Services\Mail\Contracts\MessageInterface;
use Architect\Services\Mail\Message;

/**
 * Sendmail mailer driver — uses PHP's mail() function.
 */
class SendmailMailer implements MailerInterface
{
    public function __construct(
        private readonly string $sendmailPath = '/usr/sbin/sendmail',
        private readonly string $additionalParams = '-f{noreply}',
    ) {
    }

    public function send(MessageInterface $message): bool
    {
        if (!function_exists('mail')) {
            return false;
        }

        $headers = $this->buildHeaders($message);
        $to = $this->buildTo($message);
        $body = $message->isHtml() ? $message->getHtmlBody() : $message->getTextBody();

        $additionalParams = str_replace(
            '{noreply}',
            array_key_first($message->getFrom()) ?: 'noreply@localhost',
            $this->additionalParams
        );

        return mail($to, $message->getSubject(), $body, $headers, $additionalParams);
    }

    public function sendRaw(MessageInterface $message): array
    {
        $output = $this->buildHeaders($message) . "\n\n" .
                  $this->buildTo($message) . "\n" .
                  $message->getSubject() . "\n\n" .
                  ($message->isHtml() ? $message->getHtmlBody() : $message->getTextBody());

        $success = $this->send($message);

        return [
            'success' => $success,
            'output' => $output,
        ];
    }

    public function getName(): string
    {
        return 'sendmail';
    }

    public function isAvailable(): bool
    {
        return function_exists('mail');
    }

    private function buildHeaders(MessageInterface $message): string
    {
        $headers = [];

        foreach ($message->getFrom() as $email => $name) {
            $headers[] = 'From: ' . Message::formatAddress($email, $name);
        }

        $replyTo = $message->getHeaders()['Reply-To'] ?? null;
        if ($replyTo) {
            $headers[] = 'Reply-To: ' . $replyTo;
        }

        foreach ($message->getCc() as $email => $name) {
            $headers[] = 'Cc: ' . Message::formatAddress($email, $name);
        }

        foreach ($message->getBcc() as $email => $name) {
            $headers[] = 'Bcc: ' . Message::formatAddress($email, $name);
        }

        $headers[] = 'MIME-Version: 1.0';

        if ($message->isHtml() && $message->getTextBody() !== '') {
            $boundary = md5(uniqid((string) time(), true));
            $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
        } else {
            $headers[] = 'Content-Type: ' .
                ($message->isHtml() ? 'text/html' : 'text/plain') .
                '; charset=UTF-8';
        }

        return implode("\r\n", $headers);
    }

    private function buildTo(MessageInterface $message): string
    {
        $to = [];
        foreach ($message->getTo() as $email => $name) {
            $to[] = Message::formatAddress($email, $name);
        }
        return implode(', ', $to);
    }
}
