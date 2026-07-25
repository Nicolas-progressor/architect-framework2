<?php

declare(strict_types=1);

namespace Architect\Services\Form;

use Architect\Services\Form\Interfaces\CSRFTokenManagerInterface;
use Architect\Services\Form\Interfaces\FormBuilderInterface;
use Architect\Services\Form\Traits\EscaperTrait;

/**
 * Class FormBuilder
 *
 * Генерация HTML-элементов форм.
 * Реализует интерфейс FormBuilderInterface.
 *
 * @package Architect\Services\Form
 */
class FormBuilder implements FormBuilderInterface
{
    use EscaperTrait;
    /**
     * CSRF Token Manager (через интерфейс)
     */
    protected CSRFTokenManagerInterface $csrf;

    /**
     * Имя текущей формы
     */
    protected string $formName = '';

    /**
     * Атрибуты текущей формы
     */
    protected array $formAttributes = [];

    /**
     * Данные формы (для заполнения полей)
     */
    protected array $data = [];

    /**
     * Ошибки валидации
     */
    protected array $errors = [];

    /**
     * Имя класса для ошибок
     */
    protected string $errorClass = 'is-invalid';

    /**
     * Имя класса для успешного заполнения
     */
    protected string $validClass = 'is-valid';

    /**
     * Конструктор
     * 
     * @param CSRFTokenManagerInterface|null $csrf CSRF менеджер
     */
    public function __construct(?CSRFTokenManagerInterface $csrf = null)
    {
        $this->csrf = $csrf ?? new CSRFTokenManager();
    }

    /**
     * Открыть форму
     * 
     * @param string $action URL действия
     * @param string $method Метод HTTP
     * @param array $attributes Дополнительные атрибуты
     * @return string HTML формы
     */
    public function open(string $action, string $method = 'POST', array $attributes = []): string
    {
        $this->formName = $attributes['name'] ?? 'form_' . md5($action);
        $this->formAttributes = $attributes;

        $attrs = $this->buildAttributes(array_merge([
            'action' => $this->escape($action),
            'method' => strtoupper($method),
        ], $attributes));

        $html = '<form' . $attrs . '>';
        
        // Добавляем CSRF токен для POST форм
        if (strtoupper($method) === 'POST') {
            $html .= $this->csrf->getTokenField($this->formName);
        }

        return $html;
    }

    /**
     * Открыть форму для регистрации (алиас)
     */
    public function register(string $action = '/register', array $attributes = []): string
    {
        return $this->open($action, 'POST', array_merge(['name' => 'register_form'], $attributes));
    }

    /**
     * Открыть форму для входа (алиас)
     */
    public function login(string $action = '/login', array $attributes = []): string
    {
        return $this->open($action, 'POST', array_merge(['name' => 'login_form'], $attributes));
    }

    /**
     * Закрыть форму
     * 
     * @return string
     */
    public function close(): string
    {
        $this->formName = '';
        $this->formAttributes = [];
        return '</form>';
    }

    /**
     * Текстовое поле
     * 
     * @param string $name Имя поля
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function textField(string $name, mixed $value = '', array $attributes = []): string
    {
        $value = $this->getValue($name, $value);
        return $this->input('text', $name, $value, $attributes);
    }

    /**
     * Поле email
     * 
     * @param string $name Имя поля
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function emailField(string $name, mixed $value = '', array $attributes = []): string
    {
        $value = $this->getValue($name, $value);
        return $this->input('email', $name, $value, $attributes);
    }

    /**
     * Поле пароля
     * 
     * @param string $name Имя поля
     * @param array $attributes Атрибуты
     * @return string
     */
    public function passwordField(string $name, array $attributes = []): string
    {
        return $this->input('password', $name, '', $attributes);
    }

    /**
     * Скрытое поле
     * 
     * @param string $name Имя поля
     * @param mixed $value Значение
     * @return string
     */
    public function hidden(string $name, mixed $value = ''): string
    {
        return $this->input('hidden', $name, $value, []);
    }

    /**
     * Числовое поле
     * 
     * @param string $name Имя поля
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function numberField(string $name, mixed $value = '', array $attributes = []): string
    {
        $value = $this->getValue($name, $value);
        return $this->input('number', $name, $value, $attributes);
    }

    /**
     * Поле поиска
     * 
     * @param string $name Имя поля
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function searchField(string $name, mixed $value = '', array $attributes = []): string
    {
        $value = $this->getValue($name, $value);
        return $this->input('search', $name, $value, $attributes);
    }

    /**
     * Телефонное поле
     * 
     * @param string $name Имя поля
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function telField(string $name, mixed $value = '', array $attributes = []): string
    {
        $value = $this->getValue($name, $value);
        return $this->input('tel', $name, $value, $attributes);
    }

    /**
     * URL поле
     * 
     * @param string $name Имя поля
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function urlField(string $name, mixed $value = '', array $attributes = []): string
    {
        $value = $this->getValue($name, $value);
        return $this->input('url', $name, $value, $attributes);
    }

    /**
     * Текстовая область
     * 
     * @param string $name Имя поля
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function textarea(string $name, mixed $value = '', array $attributes = []): string
    {
        $value = $this->getValue($name, $value);
        $attrs = $this->buildAttributes($attributes);
        
        $rows = $attributes['rows'] ?? 5;
        
        $html = "<textarea name=\"{$this->escape($name)}\"{$attrs}>";
        $html .= $this->escape((string)$value);
        $html .= '</textarea>';
        
        $html .= $this->renderError($name);
        
        return $html;
    }

    /**
     * Выпадающий список
     * 
     * @param string $name Имя поля
     * @param array $options Варианты [value => label] или [[value, label, selected]]
     * @param mixed $selected Выбранное значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function select(string $name, array $options, mixed $selected = null, array $attributes = []): string
    {
        $selected = $this->getValue($name, $selected);
        
        // Если selected передан как null, используем значение из data
        if ($selected === null && isset($this->data[$name])) {
            $selected = $this->data[$name];
        }
        
        $attrs = $this->buildAttributes($attributes);
        
        $html = "<select name=\"{$this->escape($name)}\"{$attrs}>";
        
        foreach ($options as $key => $option) {
            if (is_array($option)) {
                $value = $option[0] ?? '';
                $label = $option[1] ?? $value;
                $isSelected = isset($option[2]) ? $option[2] : ($value == $selected);
            } else {
                $value = $key;
                $label = $option;
                $isSelected = ($value == $selected);
            }
            
            $selectedAttr = $isSelected ? ' selected' : '';
            $html .= "<option value=\"{$this->escape((string)$value)}\"{$selectedAttr}>";
            $html .= $this->escape($label);
            $html .= '</option>';
        }
        
        $html .= '</select>';
        
        $html .= $this->renderError($name);
        
        return $html;
    }

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
    public function checkbox(string $name, mixed $value = '1', bool $checked = false, string $label = '', array $attributes = []): string
    {
        $checked = $this->isChecked($name, $value, $checked);
        
        $attrs = $this->buildAttributes($attributes);
        $checkedAttr = $checked ? ' checked' : '';
        
        $html = '<div class="form-check">';
        $html .= "<input type=\"checkbox\" name=\"{$this->escape($name)}\" value=\"{$this->escape((string)$value)}\"{$attrs}{$checkedAttr}>";
        
        if ($label) {
            $labelFor = $attributes['id'] ?? $name;
            $html .= "<label class=\"form-check-label\" for=\"{$this->escape($labelFor)}\">{$this->escape($label)}</label>";
        }
        
        $html .= $this->renderError($name);
        $html .= '</div>';
        
        return $html;
    }

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
    public function radio(string $name, mixed $value = '1', bool $checked = false, string $label = '', array $attributes = []): string
    {
        $checked = $this->isChecked($name, $value, $checked);
        
        $attrs = $this->buildAttributes($attributes);
        $checkedAttr = $checked ? ' checked' : '';
        
        $html = '<div class="form-check">';
        $html .= "<input type=\"radio\" name=\"{$this->escape($name)}\" value=\"{$this->escape((string)$value)}\"{$attrs}{$checkedAttr}>";
        
        if ($label) {
            $labelFor = $attributes['id'] ?? $name . '_' . $value;
            $html .= "<label class=\"form-check-label\" for=\"{$this->escape($labelFor)}\">{$this->escape($label)}</label>";
        }
        
        $html .= $this->renderError($name);
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Кнопка отправки
     * 
     * @param string $label Текст кнопки
     * @param array $attributes Атрибуты
     * @return string
     */
    public function submitButton(string $label = 'Отправить', array $attributes = []): string
    {
        $attrs = $this->buildAttributes(array_merge([
            'type' => 'submit',
        ], $attributes));
        
        return '<button' . $attrs . '>' . $this->escape($label) . '</button>';
    }

    /**
     * Кнопка сброса
     * 
     * @param string $label Текст кнопки
     * @param array $attributes Атрибуты
     * @return string
     */
    public function resetButton(string $label = 'Сбросить', array $attributes = []): string
    {
        $attrs = $this->buildAttributes(array_merge([
            'type' => 'reset',
        ], $attributes));
        
        return '<button' . $attrs . '>' . $this->escape($label) . '</button>';
    }

    /**
     * Кнопка-ссылка
     * 
     * @param string $label Текст
     * @param string $url URL
     * @param array $attributes Атрибуты
     * @return string
     */
    public function button(string $label, string $url = '', array $attributes = []): string
    {
        $attrs = $this->buildAttributes($attributes);
        
        if ($url) {
            return "<a href=\"{$this->escape($url)}\"{$attrs}>{$this->escape($label)}</a>";
        }
        
        return '<button type="button"' . $attrs . '>' . $this->escape($label) . '</button>';
    }

    /**
     * Файл
     * 
     * @param string $name Имя поля
     * @param array $attributes Атрибуты
     * @return string
     */
    public function fileField(string $name, array $attributes = []): string
    {
        $attrs = $this->buildAttributes(array_merge([
            'type' => 'file',
        ], $attributes));
        
        $html = "<input name=\"{$this->escape($name)}\"{$attrs}>";
        $html .= $this->renderError($name);
        
        return $html;
    }

    /**
     * Дата
     * 
     * @param string $name Имя поля
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function dateField(string $name, mixed $value = '', array $attributes = []): string
    {
        $value = $this->getValue($name, $value);
        return $this->input('date', $name, $value, $attributes);
    }

    /**
     * Время
     * 
     * @param string $name Имя поля
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function timeField(string $name, mixed $value = '', array $attributes = []): string
    {
        $value = $this->getValue($name, $value);
        return $this->input('time', $name, $value, $attributes);
    }

    /**
     * Дата и время
     * 
     * @param string $name Имя поля
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function datetimeField(string $name, mixed $value = '', array $attributes = []): string
    {
        $value = $this->getValue($name, $value);
        return $this->input('datetime-local', $name, $value, $attributes);
    }

    /**
     * Цвет
     * 
     * @param string $name Имя поля
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function colorField(string $name, mixed $value = '#000000', array $attributes = []): string
    {
        $value = $this->getValue($name, $value);
        return $this->input('color', $name, $value, $attributes);
    }

    /**
     * Диапазон
     * 
     * @param string $name Имя поля
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function rangeField(string $name, mixed $value = 50, array $attributes = []): string
    {
        $value = $this->getValue($name, $value);
        return $this->input('range', $name, $value, $attributes);
    }

    // ========== Методы для работы с данными ==========

    /**
     * Установить данные формы
     * 
     * @param array $data Данные
     * @return static
     */
    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Установить ошибки валидации
     * 
     * @param array $errors Ошибки
     * @return static
     */
    public function setErrors(array $errors): static
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * Получить старое значение (для сохранения при ошибке)
     * 
     * @param string $name Имя поля
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function getValue(string $name, mixed $default = ''): mixed
    {
        return $this->data[$name] ?? $default;
    }

    /**
     * Проверить, отмечен ли чекбокс/радио
     * 
     * @param string $name Имя поля
     * @param mixed $value Значение
     * @param bool $default Значение по умолчанию
     * @return bool
     */
    protected function isChecked(string $name, mixed $value, bool $default = false): bool
    {
        if (isset($this->data[$name])) {
            return $this->data[$name] == $value;
        }
        return $default;
    }

    // ========== Методы для рендеринга ошибок ==========

    /**
     * Отрендерить ошибку для поля
     * 
     * @param string $name Имя поля
     * @return string
     */
    public function renderError(string $name): string
    {
        if (!isset($this->errors[$name]) || empty($this->errors[$name])) {
            return '';
        }

        $message = $this->errors[$name][0];
        
        return '<div class="invalid-feedback">' . $this->escape($message) . '</div>';
    }

    /**
     * Отрендерить все ошибки
     * 
     * @param string $class CSS класс для контейнера
     * @return string
     */
    public function renderAllErrors(string $class = 'alert alert-danger'): string
    {
        if (empty($this->errors)) {
            return '';
        }

        $html = '<div class="' . $this->escape($class) . '">';
        $html .= '<ul class="mb-0">';
        
        foreach ($this->errors as $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $html .= '<li>' . $this->escape($error) . '</li>';
            }
        }
        
        $html .= '</ul>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Добавить CSS класс ошибки к атрибутам
     * 
     * @param array $attributes Атрибуты
     * @param string $field Имя поля
     * @return array
     */
    public function addErrorClass(array $attributes, string $field): array
    {
        if ($this->hasError($field)) {
            $attributes['class'] = ($attributes['class'] ?? '') . ' ' . $this->errorClass;
        }
        return $attributes;
    }

    /**
     * Проверить, есть ли ошибка для поля
     * 
     * @param string $field Имя поля
     * @return bool
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]) && !empty($this->errors[$field]);
    }

    // ========== Вспомогательные методы ==========

    /**
     * Создать input элемент
     * 
     * @param string $type Тип input
     * @param string $name Имя поля
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    protected function input(string $type, string $name, mixed $value, array $attributes): string
    {
        $attributes = $this->addErrorClass($attributes, $name);
        
        $attrs = $this->buildAttributes(array_merge([
            'type' => $type,
            'name' => $name,
            'value' => $value,
        ], $attributes));
        
        $html = "<input{$attrs}>";
        $html .= $this->renderError($name);
        
        return $html;
    }

    /**
     * Построить строку атрибутов
     * 
     * @param array $attributes Атрибуты
     * @return string
     */
    protected function buildAttributes(array $attributes): string
    {
        $html = '';
        
        foreach ($attributes as $name => $value) {
            // Пропускаем некоторые атрибуты
            if (in_array($name, ['name', 'value', 'type'], true)) {
                continue;
            }
            
            if ($value === true) {
                $html .= ' ' . $this->escape($name);
            } elseif ($value !== false && $value !== null) {
                $html .= ' ' . $this->escape($name) . '="' . $this->escape((string)$value) . '"';
            }
        }
        
        return $html;
    }


    // ========== Статические методы ==========

    /**
     * Создать новый экземпляр
     * 
     * @return self
     */
    public static function create(): self
    {
        return new self();
    }
}
