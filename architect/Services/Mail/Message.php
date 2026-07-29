<?php

declare(strict_types=1);

namespace Architect\Services\Mail;

use Architect\Services\Mail\Contracts\MessageInterface;

/**
 * Email message value object.
 */
class Message implements MessageInterface
{
    /** @var array<string, string> email => name */
    private array $from = [];

    /** @var array<string, string> email => name */
    private array $to = [];

    /** @var array<string, string> email => name */
    private array $cc = [];

    /** @var array<string, string> email => name */
    private array $bcc = [];

    private string $subject = '';
    private string $htmlBody = '';
    private string $textBody = '';
    private bool $isHtml = true;

    /** @var array{name: string, content: string, mimeType: string, disposition: string}[] */
    private array $attachments = [];

    /** @var array<string, string> header => value */
    private array $headers = [];

    public static function create(): static
    {
        return new static();
    }

    public function from(string $address, string $name = ''): static
    {
        $this->from = [$address => $name];
        return $this;
    }

    public function to(string $address, string $name = ''): static
    {
        $this->to[$address] = $name;
        return $this;
    }

    public function cc(string $address, string $name = ''): static
    {
        $this->cc[$address] = $name;
        return $this;
    }

    public function bcc(string $address, string $name = ''): static
    {
        $this->bcc[$address] = $name;
        return $this;
    }

    public function subject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function html(string $body): static
    {
        $this->htmlBody = $body;
        $this->isHtml = true;
        return $this;
    }

    public function text(string $body): static
    {
        $this->textBody = $body;
        $this->isHtml = false;
        return $this;
    }

    public function body(string $body, bool $isHtml = true): static
    {
        if ($isHtml) {
            $this->htmlBody = $body;
            $this->isHtml = true;
        } else {
            $this->textBody = $body;
            $this->isHtml = false;
        }
        return $this;
    }

    public function attach(string $filePath, string $name = '', string $mimeType = ''): static
    {
        $this->attachments[] = [
            'path' => $filePath,
            'name' => $name ?: basename($filePath),
            'mimeType' => $mimeType ?: mime_content_type($filePath) ?: 'application/octet-stream',
            'disposition' => 'attachment',
        ];
        return $this;
    }

    public function attachContent(string $content, string $name, string $mimeType = 'application/octet-stream'): static
    {
        $this->attachments[] = [
            'content' => $content,
            'name' => $name,
            'mimeType' => $mimeType,
            'disposition' => 'attachment',
        ];
        return $this;
    }

    public function header(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function getFrom(): array
    {
        return $this->from;
    }

    public function getTo(): array
    {
        return $this->to;
    }

    public function getCc(): array
    {
        return $this->cc;
    }

    public function getBcc(): array
    {
        return $this->bcc;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getBody(): string
    {
        return $this->isHtml ? $this->htmlBody : $this->textBody;
    }

    public function getHtmlBody(): string
    {
        return $this->htmlBody;
    }

    public function getTextBody(): string
    {
        return $this->textBody;
    }

    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function isHtml(): bool
    {
        return $this->isHtml;
    }

    /**
     * Get formatted address string (e.g. "Name <email>").
     */
    public static function formatAddress(string $email, string $name = ''): string
    {
        if ($name === '') {
            return $email;
        }
        return '"' . str_replace('"', '\\"', $name) . '" <' . $email . '>';
    }
}
