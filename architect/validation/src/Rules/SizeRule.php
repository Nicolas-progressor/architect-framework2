<?php

declare(strict_types=1);

namespace Architect\Validation\Rules;

use Architect\Validation\Validator;

class SizeRule extends Rule
{
    /**
     * Проверяет точное значение/длину
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
        $size = $this->parameter(0);

        if (is_null($size) || !is_numeric($size)) {
            return false;
        }

        $size = (float) $size;

        if (is_numeric($value)) {
            return (float) $value == $size;
        }

        if (is_string($value)) {
            return mb_strlen($value) == $size;
        }

        if (is_array($value)) {
            return count($value) == $size;
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
            'numeric' => 'Поле :attribute должно быть равным :1.',
            'string' => 'Поле :attribute должно содержать :1 символов.',
            'array' => 'Поле :attribute должно содержать :1 элементов.',
        ];

        return $this->replacePlaceholders(
            $messages[$type] ?? 'Поле :attribute должно быть равным :1.',
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
