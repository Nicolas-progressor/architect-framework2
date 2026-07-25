<?php

declare(strict_types=1);

namespace Architect\Validation\Rules;

use Architect\Validation\Validator;

class InRule extends Rule
{
    /**
     * Проверяет, содержится ли значение в списке допустимых значений
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
            return false;
        }

        return in_array($value, $parameters, true);
    }

    /**
     * Получает сообщение об ошибке
     *
     * @param string $attribute
     * @return string
     */
    public function message(string $attribute): string
    {
        $allowed = implode(', ', $this->parameters);
        return $this->replacePlaceholders(
            'Поле :attribute должно быть одним из допустимых значений: :1.',
            $attribute
        );
    }
}