<?php

declare(strict_types=1);

namespace Architect\Services\Mail;

use Architect\Services\Mail\Contracts\MailerInterface;
use Architect\Services\Mail\Drivers\LogMailer;
use Architect\Services\Mail\Drivers\SendmailMailer;
use Architect\Services\Mail\Drivers\SmtpMailer;

/**
 * Mailer Manager — manages mail drivers and provides unified API.
 */
class MailerManager
{
    /** @var array<string, MailerInterface> Resolved driver instances */
    private array $drivers = [];

    private string $defaultDriver = 'log';

    /** @var array<string, array> Driver configurations */
    private array $config = [];

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->defaultDriver = $config['default'] ?? 'log';
    }

    /**
     * Get a mailer driver by name.
     */
    public function driver(?string $name = null): MailerInterface
    {
        $name ??= $this->defaultDriver;

        if (!isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->createDriver($name);
        }

        return $this->drivers[$name];
    }

    /**
     * Register a custom driver instance.
     */
    public function extend(string $name, MailerInterface $driver): void
    {
        $this->drivers[$name] = $driver;
    }

    /**
     * Send mail via the default driver.
     */
    public function send(Message $message): bool
    {
        return $this->driver()->send($message);
    }

    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->defaultDriver;
    }

    /**
     * Set the default driver name.
     */
    public function setDefaultDriver(string $name): void
    {
        $this->defaultDriver = $name;
    }

    private function createDriver(string $name): MailerInterface
    {
        $driverConfig = $this->config[$name] ?? [];

        return match ($name) {
            'log' => new LogMailer($driverConfig['log_path'] ?? null),
            'sendmail' => new SendmailMailer(
                $driverConfig['sendmail_path'] ?? '/usr/sbin/sendmail',
                $driverConfig['additional_params'] ?? '-f{noreply}',
            ),
            'smtp' => new SmtpMailer(
                host: $driverConfig['host'] ?? '127.0.0.1',
                port: $driverConfig['port'] ?? 587,
                username: $driverConfig['username'] ?? '',
                password: $driverConfig['password'] ?? '',
                encryption: $driverConfig['encryption'] ?? 'tls',
                timeout: $driverConfig['timeout'] ?? 10,
            ),
            default => throw new \InvalidArgumentException("Mailer driver '{$name}' is not supported."),
        };
    }
}
