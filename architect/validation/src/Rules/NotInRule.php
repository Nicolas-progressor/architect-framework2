<?php

declare(strict_types=1);

namespace Architect\Validation\Rules;

use Architect\Validation\Validator;

class NotInRule extends Rule
{
    /**
     * Проверяет, что значение не содержится в списке запрещенных значений
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
        
        if (empty($parameters)) {
            return true;
        }

        return !in_array($value, $parameters, true);
    }

    /**
     * Получает сообщение об ошибке
     *
     * @param string $attribute
     * @return string
     */
    public function message(string $attribute): string
    {
        $forbidden = implode(', ', $this->parameters);
        return $this->replacePlaceholders(
            'Поле :attribute не должно быть одним из запрещенных значений: :1.',
            $attribute
        );
    }
}