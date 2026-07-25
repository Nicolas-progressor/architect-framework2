<?php

namespace Architect\AuthSystem\Events;

/**
 * Базовый класс события авторизации.
 */
abstract class AuthEvent
{
    /**
     * @var string Имя события
     */
    protected $name;

    /**
     * @var mixed Данные события
     */
    protected $payload;

    /**
     * @var \DateTimeImmutable Время события
     */
    protected $timestamp;

    /**
     * @var bool Остановлено ли распространение события
     */
    protected $propagationStopped = false;

    /**
     * @param string $name
     * @param mixed $payload
     */
    public function __construct(string $name, $payload = null)
    {
        $this->name = $name;
        $this->payload = $payload;
        $this->timestamp = new \DateTimeImmutable();
    }

    /**
     * Получить имя события.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Получить данные события.
     *
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * Получить время события.
     *
     * @return \DateTimeImmutable
     */
    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->timestamp;
    }

    /**
     * Остановить распространение события.
     *
     * @return void
     */
    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    /**
     * Проверить, остановлено ли распространение.
     *
     * @return bool
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * Преобразовать в массив для логирования.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'payload' => $this->payload,
            'timestamp' => $this->timestamp->format(\DateTimeInterface::ISO8601),
            'propagation_stopped' => $this->propagationStopped,
        ];
    }
}