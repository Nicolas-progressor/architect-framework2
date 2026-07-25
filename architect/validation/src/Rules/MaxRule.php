<?php

declare(strict_types=1);

namespace Architect\Validation\Rules;

use Architect\Validation\Validator;

class MaxRule extends Rule
{
    /**
     * Проверяет максимальное значение/длину
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @param Validator $validator
     * @return bool
     */
    public function passes(string $attribute, $value, array $parameters, Validator $validator): bool
    {
        $this->setParameters($parameters);
        $max = $this->parameter(0);

        if (is_null($max) || !is_numeric($max)) {
            return false;
        }

        $max = (float) $max;

        if (is_numeric($value)) {
            return (float) $value <= $max;
        }

        if (is_string($value)) {
            return mb_strlen($value) <= $max;
        }

        if (is_array($value)) {
            return count($value) <= $max;
        }

        return false;
    }

    /**
     * Получает сообщение об ошибке
     *
     * @param string $attribute
     * @return string
     */
    public function message(string $attribute): string
    {
        $type = $this->guessType($attribute);

        $messages = [
            'numeric' => 'Поле :attribute должно быть не больше :1.',
            'string' => 'Поле :attribute должно содержать не больше :1 символов.',
            'array' => 'Поле :attribute должно содержать не больше :1 элементов.',
        ];

        return $this->replacePlaceholders(
            $messages[$type] ?? 'Поле :attribute должно быть не больше :1.',
            $attribute
        );
    }

    /**
     * Определяет тип значения для сообщения об ошибке
     *
     * @param string $attribute
     * @return string
     */
    private function guessType(string $attribute): string
    {
        return 'string';
    }
}
