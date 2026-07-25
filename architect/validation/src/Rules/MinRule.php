<?php

declare(strict_types=1);

namespace Architect\Validation\Rules;

use Architect\Validation\Validator;

class MinRule extends Rule
{
    /**
     * Проверяет минимальное значение/длину
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
        $min = $this->parameter(0);

        if (is_null($min) || !is_numeric($min)) {
            return false;
        }

        $min = (float) $min;

        if (is_numeric($value)) {
            return (float) $value >= $min;
        }

        if (is_string($value)) {
            return mb_strlen($value) >= $min;
        }

        if (is_array($value)) {
            return count($value) >= $min;
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
            'numeric' => 'Поле :attribute должно быть не меньше :1.',
            'string' => 'Поле :attribute должно содержать не меньше :1 символов.',
            'array' => 'Поле :attribute должно содержать не меньше :1 элементов.',
        ];

        return $this->replacePlaceholders(
            $messages[$type] ?? 'Поле :attribute должно быть не меньше :1.',
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
        // В реальной реализации можно использовать информацию о типе из правил
        // Пока возвращаем строку как наиболее частый случай
        return 'string';
    }
}
