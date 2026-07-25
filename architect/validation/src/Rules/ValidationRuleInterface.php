<?php

declare(strict_types=1);

namespace Architect\Validation\Rules;

use Architect\Validation\Validator;

interface ValidationRuleInterface
{
    /**
     * Проверяет, соответствует ли значение правилу
     *
     * @param string $attribute Название атрибута
     * @param mixed $value Значение для проверки
     * @param array $parameters Параметры правила
     * @param Validator $validator Экземпляр валидатора
     * @return bool
     */
    public function passes(string $attribute, $value, array $parameters, Validator $validator): bool;

    /**
     * Получает сообщение об ошибке
     *
     * @param string $attribute Название атрибута
     * @return string
     */
    public function message(string $attribute): string;
}
