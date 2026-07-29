<?php

declare(strict_types=1);

namespace Architect\Services\Mail\Contracts;

/**
 * Mailer driver interface.
 */
interface MailerInterface
{
    /**
     * Send a message.
     *
     * @return bool True if sent successfully
     */
    public function send(MessageInterface $message): bool;

    /**
     * Send a message and return raw transport output.
     *
     * @return array{success: bool, output: string}
     */
    public function sendRaw(MessageInterface $message): array;

    /**
     * Get driver name.
     */
    public function getName(): string;

    /**
     * Check if driver is available.
     */
    public function isAvailable(): bool;
}
