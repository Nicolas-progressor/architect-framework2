<?php

declare(strict_types=1);

namespace Architect\Validation\Rules;

use Architect\Validation\Validator;

class RegexRule extends Rule
{
    /**
     * Проверяет значение по регулярному выражению
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
        $pattern = $this->parameter(0);

        if (empty($pattern) || !is_string($pattern)) {
            return false;
        }

        if (!is_string($value)) {
            return false;
        }

        return preg_match($pattern, $value) === 1;
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
            'Поле :attribute имеет неверный формат.',
            $attribute
        );
    }
}