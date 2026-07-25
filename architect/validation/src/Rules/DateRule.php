<?php

declare(strict_types=1);

namespace Architect\Validation\Rules;

use Architect\Validation\Validator;

class DateRule extends Rule
{
    /**
     * Проверяет, является ли значение корректной датой
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

        $this->setParameters($parameters);
        $format = $this->parameter(0) ?? 'Y-m-d';

        $date = \DateTime::createFromFormat($format, $value);
        return $date !== false && $date->format($format) === $value;
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
            'Поле :attribute должно быть корректной датой.',
            $attribute
        );
    }
}