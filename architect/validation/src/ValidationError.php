<?php

declare(strict_types=1);

namespace Architect\Validation;

class ValidationError
{
    protected string $attribute;
    protected string $message;
    protected array $parameters;
    
    public function __construct(string $attribute, string $message, array $parameters = [])
    {
        $this->attribute = $attribute;
        $this->message = $message;
        $this->parameters = $parameters;
    }
    
    public function getAttribute(): string
    {
        return $this->attribute;
    }
    
    public function getMessage(): string
    {
        return $this->message;
    }
    
    public function getParameters(): array
    {
        return $this->parameters;
    }
    
    /**
     * Форматирует сообщение с подстановкой параметров
     *
     * @return string
     */
    public function format(): string
    {
        $message = $this->message;
        
        foreach ($this->parameters as $key => $value) {
            $placeholder = ':' . $key;
            $message = str_replace($placeholder, (string) $value, $message);
        }
        
        // Заменяем :attribute на имя атрибута
        $message = str_replace(':attribute', $this->attribute, $message);
        
        return $message;
    }
    
    /**
     * Преобразует ошибку в массив
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'attribute' => $this->attribute,
            'message' => $this->format(),
            'parameters' => $this->parameters,
        ];
    }
}