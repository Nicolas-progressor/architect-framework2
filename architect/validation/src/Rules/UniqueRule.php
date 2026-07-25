<?php

declare(strict_types=1);

namespace Architect\Validation\Rules;

use Architect\Validation\Validator;

class UniqueRule extends Rule
{
    /**
     * Проверяет уникальность значения в БД
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @param Validator $validator
     * @return bool
     */
    public function passes(string $attribute, $value, array $parameters, Validator $validator): bool
    {
        // Заглушка: всегда возвращает true
        // В реальной реализации нужно выполнять запрос к БД через Axiom ORM
        return true;
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
            'Поле :attribute уже существует.',
            $attribute
        );
    }
}
