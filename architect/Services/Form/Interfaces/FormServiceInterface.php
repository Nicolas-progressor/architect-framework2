<?php

declare(strict_types=1);

namespace Architect\Services\Form\Interfaces;

/**
 * Interface FormServiceInterface
 * 
 * Основной интерфейс сервиса форм.
 */
interface FormServiceInterface
{
    /**
     * Обработать форму
     * 
     * @param string $formName Имя формы
     * @param array $validationRules Правила валидации
     * @param callable|null $callback Функция при успешной валидации
     * @return FormResult Результат обработки
     */
    public function handle(string $formName, array $validationRules, ?callable $callback = null): FormResult;

    /**
     * Проверить данные без обработки формы
     * 
     * @param array $data Данные для валидации
     * @param array $rules Правила валидации
     * @return FormResult Результат валидации
     */
    public function validate(array $data, array $rules): FormResult;

    /**
     * Сгенерировать CSRF токен
     * 
     * @param string $formName Имя формы
     * @return string
     */
    public function token(string $formName = 'default'): string;

    /**
     * Получить скрытое поле с CSRF токеном
     * 
     * @param string $formName Имя формы
     * @return string
     */
    public function tokenField(string $formName = 'default'): string;

    /**
     * Проверить CSRF токен
     * 
     * @param string $formName Имя формы
     * @param string $token Токен
     * @return bool
     */
    public function validateToken(string $formName, string $token): bool;

    /**
     * Открыть форму
     * 
     * @param string $action URL
     * @param string $method Метод
     * @param array $attributes Атрибуты
     * @return string
     */
    public function open(string $action, string $method = 'POST', array $attributes = []): string;

    /**
     * Закрыть форму
     * 
     * @return string
     */
    public function close(): string;

    /**
     * Текстовое поле
     * 
     * @param string $name Имя
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function text(string $name, mixed $value = '', array $attributes = []): string;

    /**
     * Email поле
     * 
     * @param string $name Имя
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function email(string $name, mixed $value = '', array $attributes = []): string;

    /**
     * Пароль
     * 
     * @param string $name Имя
     * @param array $attributes Атрибуты
     * @return string
     */
    public function password(string $name, array $attributes = []): string;

    /**
     * Текстовая область
     * 
     * @param string $name Имя
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function textarea(string $name, mixed $value = '', array $attributes = []): string;

    /**
     * Select
     * 
     * @param string $name Имя
     * @param array $options Варианты
     * @param mixed $selected Выбранное
     * @param array $attributes Атрибуты
     * @return string
     */
    public function select(string $name, array $options, mixed $selected = null, array $attributes = []): string;

    /**
     * Чекбокс
     * 
     * @param string $name Имя
     * @param mixed $value Значение
     * @param bool $checked Отмечен
     * @param string $label Метка
     * @param array $attributes Атрибуты
     * @return string
     */
    public function checkbox(string $name, mixed $value = '1', bool $checked = false, string $label = '', array $attributes = []): string;

    /**
     * Радиокнопка
     * 
     * @param string $name Имя
     * @param mixed $value Значение
     * @param bool $checked Отмечена
     * @param string $label Метка
     * @param array $attributes Атрибуты
     * @return string
     */
    public function radio(string $name, mixed $value = '1', bool $checked = false, string $label = '', array $attributes = []): string;

    /**
     * Кнопка отправки
     * 
     * @param string $label Текст
     * @param array $attributes Атрибуты
     * @return string
     */
    public function submit(string $label = 'Отправить', array $attributes = []): string;

    /**
     * Файл
     * 
     * @param string $name Имя
     * @param array $attributes Атрибуты
     * @return string
     */
    public function file(string $name, array $attributes = []): string;

    /**
     * Установить данные для формы
     * 
     * @param array $data Данные
     * @return static
     */
    public function setData(array $data): static;

    /**
     * Установить ошибки
     * 
     * @param array $errors Ошибки
     * @return static
     */
    public function setErrors(array $errors): static;

    /**
     * Получить ошибку для поля
     * 
     * @param string $field Поле
     * @return string
     */
    public function error(string $field): string;

    /**
     * Проверить, есть ли ошибка у поля
     * 
     * @param string $field Поле
     * @return bool
     */
    public function hasError(string $field): bool;
}
