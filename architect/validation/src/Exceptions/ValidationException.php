<?php

declare(strict_types=1);

namespace Architect\Validation\Exceptions;

use Architect\Validation\ValidationError;
use Exception;

class ValidationException extends Exception
{
    /**
     * Ошибки валидации
     *
     * @var array
     */
    protected array $errors;

    /**
     * Создает исключение с ошибками валидации
     *
     * @param array $errors Массив ошибок валидации
     * @param int $code Код исключения
     * @param Exception|null $previous Предыдущее исключение
     */
    public function __construct(array $errors, int $code = 422, Exception $previous = null)
    {
        $this->errors = $errors;
        parent::__construct('Ошибка валидации данных', $code, $previous);
    }

    /**
     * Получает ошибки валидации
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Получает ошибки в формате массива
     *
     * @return array
     */
    public function toArray(): array
    {
        $result = [];

        foreach ($this->errors as $attribute => $error) {
            if ($error instanceof ValidationError) {
                $result[$attribute] = $error->format();
            } elseif (is_array($error)) {
                $result[$attribute] = $error;
            } else {
                $result[$attribute] = (string) $error;
            }
        }

        return $result;
    }

    /**
     * Получает первое сообщение об ошибке
     *
     * @return string|null
     */
    public function getFirstError(): ?string
    {
        if (empty($this->errors)) {
            return null;
        }

        $firstError = reset($this->errors);

        if ($firstError instanceof ValidationError) {
            return $firstError->format();
        }

        if (is_array($firstError)) {
            return reset($firstError);
        }

        return (string) $firstError;
    }
}
