<?php

declare(strict_types=1);

namespace Architect\Services\Form;

use Architect\Services\Form\Interfaces\FormValidatorInterface;

/**
 * Class FormValidator
 *
 * Валидация данных формы по заданным правилам.
 * Реализует интерфейс FormValidatorInterface.
 *
 * Поддерживаемые правила:
 * - required - обязательное поле
 * - email - проверка формата email
 * - min_length:n - минимальная длина
 * - max_length:n - максимальная длина
 * - numeric - числовое значение
 * - min:n - минимальное значение
 * - max:n - максимальное значение
 * - match:field - совпадение с другим полем
 * - url - валидный URL
 * - alpha - только буквы
 * - alpha_num - буквы и цифры
 * - date - валидная дата
 *
 * @package Architect\Services\Form
 */
class FormValidator implements FormValidatorInterface
{
    /**
     * Ошибки валидации
     * @var array<string, array<int, string>>
     */
    private array $errors = [];

    /**
     * Данные для валидации
     * @var array
     */
    private array $data = [];

    /**
     * Кастомные правила валидации (экземпляра)
     * @var array<string, callable>
     */
    private array $customRules = [];

    /**
     * Метки полей для сообщений об ошибках
     * @var array<string, string>
     */
    private array $fieldLabels = [];

    // Статические кастомные правила удалены в пользу экземплярных

    /**
     * Валидировать данные
     *
     * @param array $data Данные для валидации
     * @param array $rules Правила валидации
     * @return bool True если валидация прошла успешно
     */
    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];
        $this->data = $data;

        foreach ($rules as $field => $ruleString) {
            $this->validateField($field, $ruleString);
        }

        return empty($this->errors);
    }

    /**
     * Валидировать конкретное поле
     *
     * @param string $field Имя поля
     * @param string $rulesString Строка правил (разделённых |)
     * @return void
     */
    protected function validateField(string $field, string $rulesString): void
    {
        $rules = explode('|', $rulesString);
        $value = $this->getValue($field);

        foreach ($rules as $rule) {
            if (empty($rule)) {
                continue;
            }

            // Разделяем имя правила и параметры
            $parts = explode(':', $rule, 2);
            $ruleName = $parts[0];
            $ruleParam = $parts[1] ?? null;

            // Пропускаем пустые необязательные поля
            if ($ruleName !== 'required' && $this->isEmpty($value)) {
                continue;
            }

            $this->applyRule($field, $ruleName, $ruleParam, $value);
        }
    }

    /**
     * Применить правило валидации
     *
     * @param string $field Имя поля
     * @param string $ruleName Имя правила
     * @param string|null $ruleParam Параметр правила
     * @param mixed $value Значение поля
     * @return void
     */
    protected function applyRule(string $field, string $ruleName, ?string $ruleParam, mixed $value): void
    {
        $method = 'validate' . ucfirst($ruleName);

        if (method_exists($this, $method)) {
            $result = $this->$method($value, $ruleParam);
        } elseif (isset($this->customRules[$ruleName])) {
            $result = call_user_func($this->customRules[$ruleName], $value, $ruleParam, $this->data);
        } else {
            // Неизвестное правило - пропускаем
            // Неизвестное правило - пропускаем
            return;
        }

        if (!$result) {
            $this->addError($field, $ruleName, $ruleParam);
        }
    }

    /**
     * Получить значение поля
     *
     * @param string $field Имя поля
     * @return mixed
     */
    protected function getValue(string $field): mixed
    {
        return $this->data[$field] ?? null;
    }

    /**
     * Проверить, пусто ли значение
     *
     * @param mixed $value
     * @return bool
     */
    protected function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && empty($value));
    }

    /**
     * Добавить ошибку
     *
     * @param string $field Имя поля
     * @param string $ruleName Имя правила
     * @param string|null $ruleParam Параметр правила
     * @return void
     */
    protected function addError(string $field, string $ruleName, ?string $ruleParam): void
    {
        $message = $this->getErrorMessage($field, $ruleName, $ruleParam);

        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        if (!in_array($message, $this->errors[$field], true)) {
            $this->errors[$field][] = $message;
        }
    }

    /**
     * Получить текст сообщения об ошибке
     *
     * @param string $field Имя поля
     * @param string $ruleName Имя правила
     * @param string|null $ruleParam Параметр правила
     * @return string
     */
    protected function getErrorMessage(string $field, string $ruleName, ?string $ruleParam): string
    {
        $labels = $this->getFieldLabels();
        $fieldLabel = $labels[$field] ?? $field;

        return match ($ruleName) {
            'required' => "Поле «{$fieldLabel}» обязательно для заполнения",
            'email' => "Поле «{$fieldLabel}» должно быть действительным email адресом",
            'min_length' => "Поле «{$fieldLabel}» должно содержать минимум {$ruleParam} символов",
            'max_length' => "Поле «{$fieldLabel}» должно содержать максимум {$ruleParam} символов",
            'numeric' => "Поле «{$fieldLabel}» должно быть числом",
            'min' => "Поле «{$fieldLabel}» должно быть не меньше {$ruleParam}",
            'max' => "Поле «{$fieldLabel}» должно быть не больше {$ruleParam}",
            'match' => "Поле «{$fieldLabel}» должно совпадать с полем «{$ruleParam}»",
            'url' => "Поле «{$fieldLabel}» должно быть действительным URL",
            'alpha' => "Поле «{$fieldLabel}» должно содержать только буквы",
            'alpha_num' => "Поле «{$fieldLabel}» должно содержать только буквы и цифры",
            'date' => "Поле «{$fieldLabel}» должно быть действительной датой",
            'in' => "Поле «{$fieldLabel}» должно быть одним из: {$ruleParam}",
            default => "Ошибка валидации поля «{$fieldLabel}»",
        };
    }

    /**
     * Получить метки полей (для сообщений об ошибках)
     *
     * @return array<string, string>
     */
    protected function getFieldLabels(): array
    {
        return $this->fieldLabels;
    }

    /**
     * Установить метки полей для сообщений об ошибках
     *
     * @param array $labels Метки полей [field => label]
     * @return static
     */
    public function setFieldLabels(array $labels): static
    {
        $this->fieldLabels = $labels;
        return $this;
    }

    // ========== Правила валидации ==========

    /**
     * Обязательное поле
     */
    protected function validateRequired(mixed $value): bool
    {
        return !$this->isEmpty($value);
    }

    /**
     * Email
     */
    protected function validateEmail(mixed $value): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Минимальная длина
     */
    protected function validateMinLength(mixed $value, ?string $param): bool
    {
        if (!is_string($value) || $param === null) {
            return false;
        }
        return mb_strlen($value) >= (int) $param;
    }

    /**
     * Максимальная длина
     */
    protected function validateMaxLength(mixed $value, ?string $param): bool
    {
        if (!is_string($value) || $param === null) {
            return false;
        }
        return mb_strlen($value) <= (int) $param;
    }

    /**
     * Числовое значение
     */
    protected function validateNumeric(mixed $value): bool
    {
        return is_numeric($value);
    }

    /**
     * Минимум
     */
    protected function validateMin(mixed $value, ?string $param): bool
    {
        if (!is_numeric($value) || $param === null) {
            return false;
        }
        return (float) $value >= (float) $param;
    }

    /**
     * Максимум
     */
    protected function validateMax(mixed $value, ?string $param): bool
    {
        if (!is_numeric($value) || $param === null) {
            return false;
        }
        return (float) $value <= (float) $param;
    }

    /**
     * Совпадение с другим полем
     */
    protected function validateMatch(mixed $value, ?string $param): bool
    {
        if ($param === null) {
            return false;
        }
        $compareValue = $this->getValue($param);
        return $value === $compareValue;
    }

    /**
     * URL
     */
    protected function validateUrl(mixed $value): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Только буквы
     */
    protected function validateAlpha(mixed $value): bool
    {
        return is_string($value) && preg_match('/^[a-zA-Zа-яА-ЯёЁ]+$/u', $value);
    }

    /**
     * Буквы и цифры
     */
    protected function validateAlphaNum(mixed $value): bool
    {
        return is_string($value) && preg_match('/^[a-zA-Zа-яА-ЯёЁ0-9]+$/u', $value);
    }

    /**
     * Дата
     */
    protected function validateDate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        $timestamp = strtotime($value);
        return $timestamp !== false;
    }

    /**
     * В списке
     */
    protected function validateIn(mixed $value, ?string $param): bool
    {
        if ($param === null) {
            return true;
        }
        $allowed = explode(',', $param);
        return in_array($value, array_map('trim', $allowed), true);
    }

    // ========== Методы для работы с ошибками ==========

    /**
     * Получить все ошибки
     *
     * @return array<string, array<int, string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Получить ошибки для конкретного поля
     *
     * @param string $field Имя поля
     * @return array<int, string>
     */
    public function getErrorsForField(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Получить первую ошибку для поля
     *
     * @param string $field Имя поля
     * @return string|null
     */
    public function getFirstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Проверить, есть ли ошибки
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Проверить, есть ли ошибки для конкретного поля
     *
     * @param string $field Имя поля
     * @return bool
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]) && !empty($this->errors[$field]);
    }

    /**
     * Получить первую ошибку
     *
     * @return string|null
     */
    public function getFirstErrorMessage(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }
        return null;
    }

    // ========== Методы для работы с кастомными правилами ==========

    /**
     * Зарегистрировать кастомное правило валидации (для экземпляра)
     *
     * @param string $name Имя правила
     * @param callable $callback Функция валидации (value, param, data) => bool
     * @return static
     */
    public function addRule(string $name, callable $callback): static
    {
        $this->customRules[$name] = $callback;
        return $this;
    }

    /**
     * Удалить кастомное правило (из экземпляра)
     *
     * @param string $name Имя правила
     * @return static
     */
    public function removeRule(string $name): static
    {
        unset($this->customRules[$name]);
        return $this;
    }

    // ========== Статические методы (для обратной совместимости) ==========
    // Глобальные правила удалены. Используйте addRule на экземпляре.

    /**
     * Быстрая валидация (статический метод)
     *
     * @param array $data Данные
     * @param array $rules Правила
     * @return bool|array Errors array on failure, true on success
     */
    public static function check(array $data, array $rules): bool|array
    {
        $validator = new self();

        if ($validator->validate($data, $rules)) {
            return true;
        }

        return $validator->getErrors();
    }
}
