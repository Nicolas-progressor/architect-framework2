<?php

declare(strict_types=1);

namespace Architect\Services\Form;

use Architect\Services\Form\Interfaces\CSRFTokenManagerInterface;
use Architect\Services\Form\Interfaces\FormBuilderInterface;
use Architect\Services\Form\Interfaces\FormResult;
use Architect\Services\Form\Interfaces\FormValidatorInterface;
use Architect\Services\Form\Interfaces\RequestInterface;

/**
 * Class FormHandler
 *
 * Основной класс для обработки форм.
 * Координирует работу FormBuilder и FormValidator, обрабатывает отправку формы.
 *
 * @package Architect\Services\Form
 */
class FormHandler
{
    /**
     * CSRF Token Manager (через интерфейс)
     */
    protected CSRFTokenManagerInterface $csrf;

    /**
     * Form Builder (через интерфейс)
     */
    protected FormBuilderInterface $builder;

    /**
     * Form Validator (через интерфейс)
     */
    protected FormValidatorInterface $validator;

    /**
     * Request interface
     */
    protected RequestInterface $request;

    /**
     * Данные формы
     */
    protected array $data = [];

    /**
     * Ошибки валидации
     */
    protected array $errors = [];

    /**
     * Имя текущей формы
     */
    protected string $formName = '';

    /**
     * Результат обработки
     */
    protected mixed $result = null;

    /**
     * Успешность обработки
     */
    protected bool $success = false;

    /**
     * Конструктор
     *
     * @param CSRFTokenManagerInterface|null $csrf CSRF менеджер
     * @param FormBuilderInterface|null $builder FormBuilder
     * @param FormValidatorInterface|null $validator FormValidator
     * @param RequestInterface|null $request Request
     */
    public function __construct(
        ?CSRFTokenManagerInterface $csrf = null,
        ?FormBuilderInterface $builder = null,
        ?FormValidatorInterface $validator = null,
        ?RequestInterface $request = null
    ) {
        $this->csrf = $csrf ?? new CSRFTokenManager();
        $this->builder = $builder ?? new FormBuilder($this->csrf);
        $this->validator = $validator ?? new FormValidator();
        $this->request = $request ?? new NativeRequest();
    }

    /**
     * Основной метод обработки формы
     *
     * @param string $formName Имя формы
     * @param array $validationRules Правила валидации
     * @param callable|null $callback Функция при успешной валидации
     * @return FormResult Результат обработки
     */
    public function handle(string $formName, array $validationRules, ?callable $callback = null): FormResult
    {
        $this->formName = $formName;

        // Собираем данные из Request (абстракция вместо $_POST)
        $this->data = $this->request->getPost() ?? [];

        // Если есть данные из POST - обрабатываем форму
        if (!empty($this->data)) {
            return $this->process($validationRules, $callback);
        }

        // Нет данных - показываем пустую форму
        return FormResult::success($this->data);
    }

    /**
     * Обработать данные формы
     *
     * @param array $validationRules Правила валидации
     * @param callable|null $callback Функция при успешной валидации
     * @return FormResult
     */
    protected function process(array $validationRules, ?callable $callback = null): FormResult
    {
        // Проверяем CSRF токен
        $csrfToken = $this->data['csrf_token'] ?? '';

        if (!$this->csrf->validateToken($this->formName, $csrfToken)) {
            $this->errors['csrf_token'] = ['Неверный или истёкший токен безопасности. Пожалуйста, обновите страницу и попробуйте снова.'];
            $this->applyDataToBuilder();
            return FormResult::csrfError(
                'Неверный или истёкший токен безопасности. Пожалуйста, обновите страницу и попробуйте снова.',
                $this->data
            );
        }

        // Валидируем данные
        if (!$this->validator->validate($this->data, $validationRules)) {
            $this->errors = $this->validator->getErrors();
            $this->applyDataToBuilder();
            return FormResult::validationError($this->errors, $this->data);
        }

        // Успешная валидация
        $this->success = true;

        // Вызываем callback если передан
        if ($callback !== null) {
            try {
                $this->result = $callback($this->data, $this);
            } catch (\Throwable $e) {
                $this->errors['callback'] = ['Ошибка при обработке данных: ' . $e->getMessage()];
                $this->success = false;
                $this->applyDataToBuilder();
                return FormResult::validationError($this->errors, $this->data);
            }
        }

        // Очищаем CSRF токен после успешной обработки
        $this->csrf->removeToken($this->formName);

        return FormResult::success($this->data, $this->result);
    }

    /**
     * Применить данные и ошибки к builder
     */
    protected function applyDataToBuilder(): void
    {
        if ($this->builder instanceof FormBuilder) {
            $this->builder->setData($this->data);
            $this->builder->setErrors($this->errors);
        }
    }

    /**
     * Проверить, была ли форма отправлена
     *
     * @return bool
     */
    public function isSubmitted(): bool
    {
        return $this->request->isPost();
    }

    /**
     * Проверить, успешно ли обработана форма
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Получить данные формы
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Получить конкретное значение
     *
     * @param string $key Ключ
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Получить ошибки
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Получить результат callback
     *
     * @return mixed
     */
    public function getResult(): mixed
    {
        return $this->result;
    }

    /**
     * Получить builder для генерации HTML
     *
     * @return FormBuilderInterface
     */
    public function builder(): FormBuilderInterface
    {
        $this->applyDataToBuilder();
        return $this->builder;
    }

    /**
     * Получить CSRF токен для формы
     *
     * @param string $formName Имя формы
     * @return string
     */
    public function getCSRFToken(string $formName): string
    {
        return $this->csrf->generateToken($formName);
    }

    /**
     * Получить скрытое поле с CSRF токеном
     *
     * @param string $formName Имя формы
     * @return string
     */
    public function getCSRFTokenField(string $formName): string
    {
        return $this->csrf->getTokenField($formName);
    }

    /**
     * Установить данные вручную
     *
     * @param array $data
     * @return static
     */
    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Установить ошибки вручную
     *
     * @param array $errors
     * @return static
     */
    public function setErrors(array $errors): static
    {
        $this->errors = $errors;
        return $this;
    }

    // ========== Статические методы для удобства ==========

    /**
     * Быстрая валидация данных
     *
     * @param array $data Данные
     * @param array $rules Правила
     * @return FormResult
     */
    public static function validate(array $data, array $rules): FormResult
    {
        $validator = new FormValidator();

        if ($validator->validate($data, $rules)) {
            return FormResult::success($data);
        }

        return FormResult::validationError($validator->getErrors(), $data);
    }

    /**
     * Создать и обработать форму (статический метод)
     *
     * @param string $formName Имя формы
     * @param array $rules Правила валидации
     * @param callable|null $callback Callback
     * @return FormResult
     */
    public static function run(string $formName, array $rules, ?callable $callback = null): FormResult
    {
        $handler = new self();
        return $handler->handle($formName, $rules, $callback);
    }
}
