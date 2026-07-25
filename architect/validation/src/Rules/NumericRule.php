<?php

declare(strict_types=1);

namespace Architect\Validation\Rules;

use Architect\Validation\Validator;

class NumericRule extends Rule
{
    /**
     * Проверяет, является ли значение числом
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @param Validator $validator
     * @return bool
     */
    public function passes(string $attribute, $value, array $parameters, Validator $validator): bool
    {
        return is_numeric($value);
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
            'Поле :attribute должно быть числом.',
            $attribute
        );
    }
}