<?php

declare(strict_types=1);

namespace Architect\Validation\Rules;

use Architect\Validation\Validator;

class BeforeRule extends Rule
{
    /**
     * Проверяет, что дата меньше указанной
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
        $targetDate = $this->parameter(0);

        if (empty($targetDate)) {
            return false;
        }

        try {
            $date = new \DateTime($value);
            $target = new \DateTime($targetDate);
            return $date < $target;
        } catch (\Exception $e) {
            return false;
        }
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
            'Поле :attribute должно быть датой до :1.',
            $attribute
        );
    }
}
