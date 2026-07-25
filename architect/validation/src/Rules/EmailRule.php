<?php

declare(strict_types=1);

namespace Architect\Validation\Rules;

use Architect\Validation\Validator;

class EmailRule extends Rule
{
    /**
     * Проверяет, является ли значение корректным email адресом
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @param Validator $validator
     * @return bool
     */
    public function passes(string $attribute, $value, array $parameters, Validator $validator): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Получает сообщение об ошибке
     *
     * @param string $attribute
     * @return string
     */
    public function message(string $attribute): string
    {
        return $this->replacePlaceholders(
            'Поле :attribute должно быть корректным email адресом.',
            $attribute
        );
    }
}