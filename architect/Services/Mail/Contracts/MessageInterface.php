<?php

declare(strict_types=1);

namespace Architect\Services\Mail\Contracts;

/**
 * Mail message interface.
 */
interface MessageInterface
{
    public function getFrom(): array;
    public function getTo(): array;
    public function getCc(): array;
    public function getBcc(): array;
    public function getSubject(): string;
    public function getBody(): string;
    public function getHtmlBody(): string;
    public function getTextBody(): string;
    public function getAttachments(): array;
    public function getHeaders(): array;
    public function isHtml(): bool;
}
