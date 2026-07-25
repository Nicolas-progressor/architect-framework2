<?php

declare(strict_types=1);

namespace Architect\Validation\Http;

use Architect\Validation\Validator;
use Architect\Validation\Exceptions\ValidationException;

trait RequestValidatorTrait
{
    /**
     * Валидирует данные запроса
     *
     * @param array $rules Правила валидации
     * @param array $messages Кастомные сообщения
     * @return self
     * @throws ValidationException
     */
    public function validate(array $rules, array $messages = []): self
    {
        $validator = $this->createValidator($rules, $messages);
        
        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }
        
        return $this;
    }
    
    /**
     * Получает валидированные данные
     *
     * @return array
     */
    public function validated(): array
    {
        // Этот метод должен быть реализован в классе, использующем трейт
        // и возвращать данные, прошедшие валидацию
        return $this->getValidatedData();
    }
    
    /**
     * Создает экземпляр валидатора
     *
     * @param array $rules
     * @param array $messages
     * @return Validator
     */
    protected function createValidator(array $rules, array $messages = []): Validator
    {
        // Получаем данные для валидации из запроса
        $data = $this->getValidationData();
        
        // Создаем валидатор через контейнер или напрямую
        if (function_exists('app') && app()->has('validator')) {
            $validator = app('validator');
        } else {
            $validator = new Validator();
        }
        
        return $validator->make($data, $rules, $messages);
    }
    
    /**
     * Получает данные для валидации из запроса
     *
     * @return array
     */
    protected function getValidationData(): array
    {
        // Этот метод должен быть переопределен в классе запроса
        // для предоставления данных (например, из $_POST, $_GET, JSON)
        if (method_exists($this, 'all')) {
            return $this->all();
        }
        
        return [];
    }
    
    /**
     * Получает валидированные данные (заглушка)
     *
     * @return array
     */
    protected function getValidatedData(): array
    {
        // Этот метод должен быть переопределен в классе запроса
        // для хранения валидированных данных
        return $this->getValidationData();
    }
}