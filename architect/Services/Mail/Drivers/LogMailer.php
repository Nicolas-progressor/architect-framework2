<?php

declare(strict_types=1);

namespace Architect\Services\Mail\Drivers;

use Architect\Services\Mail\Contracts\MailerInterface;
use Architect\Services\Mail\Contracts\MessageInterface;
use Architect\Services\Mail\Message;

/**
 * Log mailer driver — captures messages in memory for testing/debugging.
 */
class LogMailer implements MailerInterface
{
    /** @var array<int, array{message: MessageInterface, time: string}> */
    private array $log = [];

    private string $logPath = '';

    public function __construct(?string $logPath = null)
    {
        $this->logPath = $logPath ?? '';
    }

    public function send(MessageInterface $message): bool
    {
        $this->log[] = [
            'message' => $message,
            'time' => date('Y-m-d H:i:s'),
        ];

        if ($this->logPath !== '') {
            $this->writeToFile($message);
        }

        return true;
    }

    public function sendRaw(MessageInterface $message): array
    {
        $this->log[] = [
            'message' => $message,
            'time' => date('Y-m-d H:i:s'),
        ];

        return [
            'success' => true,
            'output' => $this->renderMessage($message),
        ];
    }

    public function getName(): string
    {
        return 'log';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    /**
     * Get all logged messages.
     *
     * @return array<int, array{message: MessageInterface, time: string}>
     */
    public function getLog(): array
    {
        return $this->log;
    }

    /**
     * Get number of sent messages.
     */
    public function count(): int
    {
        return count($this->log);
    }

    /**
     * Get the last sent message.
     */
    public function last(): ?MessageInterface
    {
        $last = end($this->log);
        return $last !== false ? $last['message'] : null;
    }

    /**
     * Clear the log.
     */
    public function reset(): void
    {
        $this->log = [];
    }

    private function renderMessage(MessageInterface $message): string
    {
        $to = implode(', ', array_map(
            fn($email, $name) => Message::formatAddress($email, $name),
            array_keys($message->getTo()),
            $message->getTo()
        ));

        $from = '';
        foreach ($message->getFrom() as $email => $name) {
            $from = Message::formatAddress($email, $name);
        }

        $output  = "From: {$from}\n";
        $output .= "To: {$to}\n";
        $output .= "Subject: {$message->getSubject()}\n";
        $output .= "Content-Type: " . ($message->isHtml() ? 'text/html' : 'text/plain') . "\n";
        $output .= "\n";
        $output .= $message->isHtml() ? $message->getHtmlBody() : $message->getTextBody();

        return $output;
    }

    private function writeToFile(MessageInterface $message): void
    {
        $content = $this->renderMessage($message);
        $content = "\n\n--- " . date('Y-m-d H:i:s') . " ---\n" . $content;

        $dir = dirname($this->logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0o755, true);
        }

        file_put_contents($this->logPath, $content, FILE_APPEND | LOCK_EX);
    }
}
