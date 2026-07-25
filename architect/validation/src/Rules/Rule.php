<?php

declare(strict_types=1);

namespace Architect\Validation\Rules;

use Architect\Validation\Validator;

abstract class Rule implements ValidationRuleInterface
{
    /**
     * Параметры правила
     *
     * @var array
     */
    protected array $parameters = [];

    /**
     * Устанавливает параметры правила
     *
     * @param array $parameters
     * @return self
     */
    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * Получает параметры правила
     *
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Получает значение параметра по индексу
     *
     * @param int $index
     * @param mixed $default
     * @return mixed
     */
    public function parameter(int $index, $default = null)
    {
        return $this->parameters[$index] ?? $default;
    }

    /**
     * Заменяет плейсхолдеры в сообщении
     *
     * @param string $message
     * @param string $attribute
     * @return string
     */
    protected function replacePlaceholders(string $message, string $attribute): string
    {
        $message = str_replace(':attribute', $attribute, $message);

        foreach ($this->parameters as $key => $value) {
            $placeholder = ':' . (is_int($key) ? $key + 1 : $key);
            $message = str_replace($placeholder, (string) $value, $message);
        }

        return $message;
    }
}