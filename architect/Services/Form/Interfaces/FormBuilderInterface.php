<?php

declare(strict_types=1);

namespace Architect\Services\Form\Interfaces;

/**
 * Interface FormBuilderInterface
 * 
 * Интерфейс билдера HTML-элементов форм.
 */
interface FormBuilderInterface
{
    /**
     * Открыть форму
     * 
     * @param string $action URL действия
     * @param string $method Метод HTTP
     * @param array $attributes Дополнительные атрибуты
     * @return string HTML формы
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
     * @param string $name Имя поля
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function textField(string $name, mixed $value = '', array $attributes = []): string;

    /**
     * Поле email
     * 
     * @param string $name Имя поля
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function emailField(string $name, mixed $value = '', array $attributes = []): string;

    /**
     * Поле пароля
     * 
     * @param string $name Имя поля
     * @param array $attributes Атрибуты
     * @return string
     */
    public function passwordField(string $name, array $attributes = []): string;

    /**
     * Скрытое поле
     * 
     * @param string $name Имя поля
     * @param mixed $value Значение
     * @return string
     */
    public function hidden(string $name, mixed $value = ''): string;

    /**
     * Числовое поле
     * 
     * @param string $name Имя поля
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function numberField(string $name, mixed $value = '', array $attributes = []): string;

    /**
     * Поле поиска
     * 
     * @param string $name Имя поля
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function searchField(string $name, mixed $value = '', array $attributes = []): string;

    /**
     * Телефонное поле
     * 
     * @param string $name Имя поля
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function telField(string $name, mixed $value = '', array $attributes = []): string;

    /**
     * URL поле
     * 
     * @param string $name Имя поля
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function urlField(string $name, mixed $value = '', array $attributes = []): string;

    /**
     * Текстовая область
     * 
     * @param string $name Имя поля
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function textarea(string $name, mixed $value = '', array $attributes = []): string;

    /**
     * Выпадающий список
     * 
     * @param string $name Имя поля
     * @param array $options Варианты
     * @param mixed $selected Выбранное значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function select(string $name, array $options, mixed $selected = null, array $attributes = []): string;

    /**
     * Чекбокс
     * 
     * @param string $name Имя поля
     * @param mixed $value Значение чекбокса
     * @param bool $checked Отмечен ли
     * @param string $label Текст метки
     * @param array $attributes Атрибуты
     * @return string
     */
    public function checkbox(string $name, mixed $value = '1', bool $checked = false, string $label = '', array $attributes = []): string;

    /**
     * Радиокнопка
     * 
     * @param string $name Имя поля
     * @param mixed $value Значение радио
     * @param bool $checked Отмечена ли
     * @param string $label Текст метки
     * @param array $attributes Атрибуты
     * @return string
     */
    public function radio(string $name, mixed $value = '1', bool $checked = false, string $label = '', array $attributes = []): string;

    /**
     * Кнопка отправки
     * 
     * @param string $label Текст кнопки
     * @param array $attributes Атрибуты
     * @return string
     */
    public function submitButton(string $label = 'Отправить', array $attributes = []): string;

    /**
     * Кнопка сброса
     * 
     * @param string $label Текст кнопки
     * @param array $attributes Атрибуты
     * @return string
     */
    public function resetButton(string $label = 'Сбросить', array $attributes = []): string;

    /**
     * Кнопка-ссылка
     * 
     * @param string $label Текст
     * @param string $url URL
     * @param array $attributes Атрибуты
     * @return string
     */
    public function button(string $label, string $url = '', array $attributes = []): string;

    /**
     * Файл
     * 
     * @param string $name Имя поля
     * @param array $attributes Атрибуты
     * @return string
     */
    public function fileField(string $name, array $attributes = []): string;

    /**
     * Дата
     * 
     * @param string $name Имя поля
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function dateField(string $name, mixed $value = '', array $attributes = []): string;

    /**
     * Время
     * 
     * @param string $name Имя поля
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function timeField(string $name, mixed $value = '', array $attributes = []): string;

    /**
     * Дата и время
     * 
     * @param string $name Имя поля
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function datetimeField(string $name, mixed $value = '', array $attributes = []): string;

    /**
     * Цвет
     * 
     * @param string $name Имя поля
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function colorField(string $name, mixed $value = '#000000', array $attributes = []): string;

    /**
     * Диапазон
     * 
     * @param string $name Имя поля
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function rangeField(string $name, mixed $value = 50, array $attributes = []): string;

    /**
     * Установить данные формы
     * 
     * @param array $data Данные
     * @return static
     */
    public function setData(array $data): static;

    /**
     * Установить ошибки валидации
     * 
     * @param array $errors Ошибки
     * @return static
     */
    public function setErrors(array $errors): static;

    /**
     * Отрендерить ошибку для поля
     * 
     * @param string $name Имя поля
     * @return string
     */
    public function renderError(string $name): string;

    /**
     * Отрендерить все ошибки
     * 
     * @param string $class CSS класс для контейнера
     * @return string
     */
    public function renderAllErrors(string $class = 'alert alert-danger'): string;

    /**
     * Проверить, есть ли ошибка для поля
     * 
     * @param string $field Имя поля
     * @return bool
     */
    public function hasError(string $field): bool;
}
