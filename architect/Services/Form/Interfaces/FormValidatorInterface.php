<?php

declare(strict_types=1);

namespace Architect\Services\Form\Interfaces;

/**
 * Interface FormValidatorInterface
 *
 * Интерфейс валидатора форм.
 */
interface FormValidatorInterface
{
    /**
     * Валидировать данные
     *
     * @param array $data Данные для валидации
     * @param array $rules Правила валидации
     * @return bool True если валидация прошла успешно
     */
    public function validate(array $data, array $rules): bool;

    /**
     * Получить все ошибки
     *
     * @return array<string, array<int, string>>
     */
    public function getErrors(): array;

    /**
     * Получить ошибки для конкретного поля
     *
     * @param string $field Имя поля
     * @return array<int, string>
     */
    public function getErrorsForField(string $field): array;

    /**
     * Получить первую ошибку для поля
     *
     * @param string $field Имя поля
     * @return string|null
     */
    public function getFirstError(string $field): ?string;

    /**
     * Проверить, есть ли ошибки
     *
     * @return bool
     */
    public function hasErrors(): bool;

    /**
     * Проверить, есть ли ошибки для конкретного поля
     *
     * @param string $field Имя поля
     * @return bool
     */
    public function hasError(string $field): bool;

    /**
     * Зарегистрировать кастомное правило валидации
     *
     * @param string $name Имя правила
     * @param callable $callback Функция валидации
     * @return static
     */
    public function addRule(string $name, callable $callback): static;

    /**
     * Удалить кастомное правило
     *
     * @param string $name Имя правила
     * @return static
     */
    public function removeRule(string $name): static;

    /**
     * Установить метки полей для сообщений об ошибках
     *
     * @param array $labels Метки полей [field => label]
     * @return static
     */
    public function setFieldLabels(array $labels): static;
}
