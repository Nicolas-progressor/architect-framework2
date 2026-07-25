<?php

declare(strict_types=1);

namespace Architect\Services\Form;

use Architect\Services\Form\Interfaces\CSRFTokenManagerInterface;
use Architect\Services\Form\Interfaces\FormBuilderInterface;
use Architect\Services\Form\Interfaces\FormResult;
use Architect\Services\Form\Interfaces\FormServiceInterface;
use Architect\Services\Form\Interfaces\FormValidatorInterface;

/**
 * Class Form
 *
 * Сервис для работы с формами в Architect Framework.
 * Реализует интерфейс FormServiceInterface.
 * Обеспечивает унифицированную работу с формами, валидацию и CSRF-защиту.
 *
 * Использование:
 *
 * $form = $container->get('form');
 *
 * // В контроллере
 * $result = $form->handle('register', [
 *     'username' => 'required|min_length:3|max_length:20',
 *     'email' => 'required|email',
 *     'password' => 'required|min_length:6'
 * ], function($data) {
 *     User::create($data);
 *     return redirect('/success');
 * });
 *
 * if ($result->hasErrors()) {
 *     // Ошибки валидации
 *     return view('register', ['errors' => $result->getErrors()]);
 * }
 *
 * @package Architect\Services\Form
 */
class Form implements FormServiceInterface
{
    /**
     * CSRF Token Manager
     */
    protected CSRFTokenManagerInterface $csrf;

    /**
     * Form Builder
     */
    protected FormBuilderInterface $builder;

    /**
     * Form Validator
     */
    protected FormValidatorInterface $validator;

    /**
     * Form Handler
     */
    protected ?FormHandler $handler = null;

    /**
     * Конструктор — внедрение зависимостей через интерфейсы
     *
     * @param CSRFTokenManagerInterface $csrf
     * @param FormBuilderInterface $builder
     * @param FormValidatorInterface $validator
     */
    public function __construct(
        CSRFTokenManagerInterface $csrf,
        FormBuilderInterface $builder,
        FormValidatorInterface $validator
    ) {
        $this->csrf = $csrf;
        $this->builder = $builder;
        $this->validator = $validator;
    }

    /**
     * Обработать форму
     *
     * @param string $formName Имя формы
     * @param array $validationRules Правила валидации
     * @param callable|null $callback Функция при успешной валидации
     * @return FormResult Результат обработки
     */
    public function handle(string $formName, array $validationRules, ?callable $callback = null): FormResult
    {
        $this->handler = new FormHandler($this->csrf, $this->builder, $this->validator);
        return $this->handler->handle($formName, $validationRules, $callback);
    }

    /**
     * Проверить данные без обработки формы
     *
     * @param array $data Данные для валидации
     * @param array $rules Правила валидации
     * @return FormResult Результат валидации
     */
    public function validate(array $data, array $rules): FormResult
    {
        return FormHandler::validate($data, $rules);
    }

    /**
     * Сгенерировать CSRF токен
     *
     * @param string $formName Имя формы
     * @return string
     */
    public function token(string $formName = 'default'): string
    {
        return $this->csrf->generateToken($formName);
    }

    /**
     * Получить скрытое поле с CSRF токеном
     *
     * @param string $formName Имя формы
     * @return string
     */
    public function tokenField(string $formName = 'default'): string
    {
        return $this->csrf->getTokenField($formName);
    }

    /**
     * Проверить CSRF токен
     *
     * @param string $formName Имя формы
     * @param string $token Токен
     * @return bool
     */
    public function validateToken(string $formName, string $token): bool
    {
        return $this->csrf->validateToken($formName, $token);
    }

    /**
     * Открыть форму
     *
     * @param string $action URL
     * @param string $method Метод
     * @param array $attributes Атрибуты
     * @return string
     */
    public function open(string $action, string $method = 'POST', array $attributes = []): string
    {
        return $this->builder->open($action, $method, $attributes);
    }

    /**
     * Закрыть форму
     *
     * @return string
     */
    public function close(): string
    {
        return $this->builder->close();
    }

    /**
     * Текстовое поле
     *
     * @param string $name Имя
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function text(string $name, mixed $value = '', array $attributes = []): string
    {
        return $this->builder->textField($name, $value, $attributes);
    }

    /**
     * Email поле
     *
     * @param string $name Имя
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function email(string $name, mixed $value = '', array $attributes = []): string
    {
        return $this->builder->emailField($name, $value, $attributes);
    }

    /**
     * Пароль
     *
     * @param string $name Имя
     * @param array $attributes Атрибуты
     * @return string
     */
    public function password(string $name, array $attributes = []): string
    {
        return $this->builder->passwordField($name, $attributes);
    }

    /**
     * Текстовая область
     *
     * @param string $name Имя
     * @param mixed $value Значение
     * @param array $attributes Атрибуты
     * @return string
     */
    public function textarea(string $name, mixed $value = '', array $attributes = []): string
    {
        return $this->builder->textarea($name, $value, $attributes);
    }

    /**
     * Select
     *
     * @param string $name Имя
     * @param array $options Варианты
     * @param mixed $selected Выбранное
     * @param array $attributes Атрибуты
     * @return string
     */
    public function select(string $name, array $options, mixed $selected = null, array $attributes = []): string
    {
        return $this->builder->select($name, $options, $selected, $attributes);
    }

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
    public function checkbox(string $name, mixed $value = '1', bool $checked = false, string $label = '', array $attributes = []): string
    {
        return $this->builder->checkbox($name, $value, $checked, $label, $attributes);
    }

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
    public function radio(string $name, mixed $value = '1', bool $checked = false, string $label = '', array $attributes = []): string
    {
        return $this->builder->radio($name, $value, $checked, $label, $attributes);
    }

    /**
     * Кнопка отправки
     *
     * @param string $label Текст
     * @param array $attributes Атрибуты
     * @return string
     */
    public function submit(string $label = 'Отправить', array $attributes = []): string
    {
        return $this->builder->submitButton($label, $attributes);
    }

    /**
     * Файл
     *
     * @param string $name Имя
     * @param array $attributes Атрибуты
     * @return string
     */
    public function file(string $name, array $attributes = []): string
    {
        return $this->builder->fileField($name, $attributes);
    }

    /**
     * Установить данные для формы
     *
     * @param array $data Данные
     * @return static
     */
    public function setData(array $data): static
    {
        $this->builder->setData($data);
        return $this;
    }

    /**
     * Установить ошибки
     *
     * @param array $errors Ошибки
     * @return static
     */
    public function setErrors(array $errors): static
    {
        $this->builder->setErrors($errors);
        return $this;
    }

    /**
     * Получить ошибку для поля
     *
     * @param string $field Поле
     * @return string
     */
    public function error(string $field): string
    {
        return $this->builder->renderError($field);
    }

    /**
     * Проверить, есть ли ошибка у поля
     *
     * @param string $field Поле
     * @return bool
     */
    public function hasError(string $field): bool
    {
        return $this->builder->hasError($field);
    }

    // ========== Дополнительные методы ==========

    /**
     * Получить builder для расширенного использования
     *
     * @return FormBuilderInterface
     */
    public function getBuilder(): FormBuilderInterface
    {
        return $this->builder;
    }

    /**
     * Получить CSRF менеджер
     *
     * @return CSRFTokenManagerInterface
     */
    public function getCSRF(): CSRFTokenManagerInterface
    {
        return $this->csrf;
    }

    /**
     * Получить валидатор
     *
     * @return FormValidatorInterface
     */
    public function getValidator(): FormValidatorInterface
    {
        return $this->validator;
    }

    /**
     * Получить handler (после handle())
     *
     * @return FormHandler|null
     */
    public function getHandler(): ?FormHandler
    {
        return $this->handler;
    }
}
