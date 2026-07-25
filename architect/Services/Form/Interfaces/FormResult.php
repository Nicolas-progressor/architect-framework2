<?php

declare(strict_types=1);

namespace Architect\Services\Form\Interfaces;

/**
 * Class FormResult
 * 
 * Объект результата обработки формы.
 * Заменяет bool|array для более чистого API.
 */
class FormResult
{
    /**
     * Успешность обработки
     */
    private bool $success;

    /**
     * Ошибки валидации
     */
    private array $errors;

    /**
     * Данные формы
     */
    private array $data;

    /**
     * Результат callback
     */
    private mixed $callbackResult;

    /**
     * Сообщение об ошибке CSRF
     */
    private ?string $csrfError = null;

    public function __construct(
        bool $success = false,
        array $errors = [],
        array $data = [],
        mixed $callbackResult = null
    ) {
        $this->success = $success;
        $this->errors = $errors;
        $this->data = $data;
        $this->callbackResult = $callbackResult;
    }

    /**
     * Успешный результат
     * 
     * @param array $data Данные формы
     * @param mixed $callbackResult Результат callback
     * @return static
     */
    public static function success(array $data = [], mixed $callbackResult = null): static
    {
        return new static(true, [], $data, $callbackResult);
    }

    /**
     * Ошибка валидации
     * 
     * @param array $errors Ошибки
     * @param array $data Данные формы
     * @return static
     */
    public static function validationError(array $errors, array $data = []): static
    {
        return new static(false, $errors, $data);
    }

    /**
     * Ошибка CSRF
     * 
     * @param string $message Сообщение об ошибке
     * @param array $data Данные формы
     * @return static
     */
    public static function csrfError(string $message, array $data = []): static
    {
        $result = new static(false, [], $data);
        $result->csrfError = $message;
        return $result;
    }

    /**
     * Проверка успешности
     * 
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Проверка наличия ошибок
     * 
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !$this->success;
    }

    /**
     * Получить ошибки
     * 
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Получить данные
     * 
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Получить конкретное значение из данных
     * 
     * @param string $key Ключ
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Получить результат callback
     * 
     * @return mixed
     */
    public function getCallbackResult(): mixed
    {
        return $this->callbackResult;
    }

    /**
     * Получить ошибку CSRF
     * 
     * @return string|null
     */
    public function getCSRFError(): ?string
    {
        return $this->csrfError;
    }

    /**
     * Проверить, есть ли ошибка CSRF
     * 
     * @return bool
     */
    public function hasCSRFError(): bool
    {
        return $this->csrfError !== null;
    }

    /**
     * Получить ошибку для конкретного поля
     * 
     * @param string $field Имя поля
     * @return string|null
     */
    public function getError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Проверить, есть ли ошибка у поля
     * 
     * @param string $field Имя поля
     * @return bool
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]) && !empty($this->errors[$field]);
    }

    /**
     * Получить первую ошибку
     * 
     * @return string|null
     */
    public function getFirstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }
        return $this->csrfError;
    }

    /**
     * Конвертировать в массив (для обратной совместимости)
     * 
     * @return bool|array
     */
    public function toLegacy(): bool|array
    {
        if ($this->success) {
            return true;
        }
        return $this->errors;
    }
}
