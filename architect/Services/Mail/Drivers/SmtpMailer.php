<?php

declare(strict_types=1);

namespace Architect\Services\Mail\Drivers;

use Architect\Services\Mail\Contracts\MailerInterface;

/**
 * SMTP mailer driver — pure PHP implementation.
 */
class SmtpMailer implements MailerInterface
{
    private mixed $socket = null;
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $encryption;
    private int $timeout;

    public function __construct(
        string $host = '127.0.0.1',
        int $port = 587,
        string $username = '',
        string $password = '',
        string $encryption = 'tls',
        int $timeout = 10,
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->encryption = $encryption;
        $this->timeout = $timeout;
    }

    public function send(MessageInterface $message): bool
    {
        try {
            $this->connect();
            $this->helo();
            $this->authenticate();
            $this->sendCommand('MAIL FROM: <' . $this->getFromAddress($message) . '>');
            $this->sendRecipients($message);
            $this->sendCommand('DATA');
            $this->sendBody($message);
            $this->sendCommand('QUIT');
            $this->disconnect();
            return true;
        } catch (\Exception) {
            $this->disconnect();
            return false;
        }
    }

    public function sendRaw(MessageInterface $message): array
    {
        try {
            $this->connect();
            $this->helo();
            $this->authenticate();
            $this->sendCommand('MAIL FROM: <' . $this->getFromAddress($message) . '>');
            $this->sendRecipients($message);
            $this->sendCommand('DATA');
            $this->sendBody($message);
            $response = $this->sendCommand('QUIT');
            $this->disconnect();
            return ['success' => true, 'output' => $response];
        } catch (\Exception $e) {
            $this->disconnect();
            return ['success' => false, 'output' => $e->getMessage()];
        }
    }

    public function getName(): string
    {
        return 'smtp';
    }

    public function isAvailable(): bool
    {
        return function_exists('fsockopen');
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    private function connect(): void
    {
        $address = "tcp://{$this->host}:{$this->port}";
        $this->socket = @stream_socket_client(
            $address,
            $errno,
            $errstr,
            $this->timeout
        );

        if ($this->socket === false) {
            throw new \RuntimeException("SMTP connection failed: {$errstr} ({$errno})");
        }

        $greeting = $this->readResponse();
        if (str_starts_with($greeting, '2')) {
            return; // OK
        }

        throw new \RuntimeException("SMTP greeting failed: {$greeting}");
    }

    private function helo(): void
    {
        $this->sendCommand('EHLO ' . gethostname());
    }

    private function authenticate(): void
    {
        if ($this->username === '' || $this->password === '') {
            return;
        }

        if ($this->encryption === 'tls' && $this->port === 587) {
            $this->sendCommand('STARTTLS');
            $context = stream_context_create(['ssl' => ['verify_peer' => false]]);
            $encrypted = stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
            if (!$encrypted) {
                throw new \RuntimeException('STARTTLS failed');
            }
            $this->sendCommand('EHLO ' . gethostname());
        }

        $this->sendCommand('AUTH LOGIN');
        $this->sendCommand(base64_encode($this->username));
        $this->sendCommand(base64_encode($this->password));
    }

    private function sendRecipients(MessageInterface $message): void
    {
        foreach (array_keys($message->getTo()) as $email) {
            $this->sendCommand('RCPT TO: <' . $email . '>');
        }
        foreach (array_keys($message->getCc()) as $email) {
            $this->sendCommand('RCPT TO: <' . $email . '>');
        }
        foreach (array_keys($message->getBcc()) as $email) {
            $this->sendCommand('RCPT TO: <' . $email . '>');
        }
    }

    private function sendBody(MessageInterface $message): void
    {
        $from = $this->getFromAddress($message);
        $to = implode(', ', array_keys($message->getTo()));
        $subject = $message->getSubject();
        $body = $message->isHtml() ? $message->getHtmlBody() : $message->getTextBody();

        $headers  = "From: {$from}\r\n";
        $headers .= "To: {$to}\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: " .
            ($message->isHtml() ? 'text/html' : 'text/plain') .
            "; charset=UTF-8\r\n";

        $this->sendCommand($headers . "\r\n" . $body);
        $this->sendCommand('.');
    }

    private function sendCommand(string $command): string
    {
        if ($this->socket === null) {
            throw new \RuntimeException('Not connected');
        }

        fwrite($this->socket, $command . "\r\n");
        return $this->readResponse();
    }

    private function readResponse(): string
    {
        if ($this->socket === null) {
            return '';
        }

        $response = '';
        while (true) {
            $line = fgets($this->socket, 4096);
            if ($line === false) {
                break;
            }
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        return trim($response);
    }

    private function disconnect(): void
    {
        if ($this->socket !== null) {
            fclose($this->socket);
            $this->socket = null;
        }
    }

    private function getFromAddress(MessageInterface $message): string
    {
        return array_key_first($message->getFrom()) ?: 'noreply@localhost';
    }
}
