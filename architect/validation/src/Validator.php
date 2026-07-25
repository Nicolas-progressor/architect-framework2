<?php

declare(strict_types=1);

namespace Architect\Validation;

use Architect\Core\Contracts\ContainerInterface;
use Architect\Validation\Exceptions\ValidationException;
use Architect\Validation\Rules\ValidationRuleInterface;

class Validator
{
    /**
     * Данные для валидации
     *
     * @var array
     */
    protected array $data = [];

    /**
     * Правила валидации
     *
     * @var array
     */
    protected array $rules = [];

    /**
     * Кастомные сообщения об ошибках
     *
     * @var array
     */
    protected array $messages = [];

    /**
     * Ошибки валидации
     *
     * @var array
     */
    protected array $errors = [];

    /**
     * Зарегистрированные кастомные правила
     *
     * @var array
     */
    protected array $customRules = [];

    /**
     * Контейнер зависимостей
     *
     * @var ContainerInterface|null
     */
    protected ?ContainerInterface $container;

    /**
     * Конструктор
     *
     * @param ContainerInterface|null $container
     */
    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Инициализирует валидатор с данными и правилами
     *
     * @param array $data Данные для валидации
     * @param array $rules Правила валидации
     * @param array $messages Кастомные сообщения об ошибках
     * @return self
     */
    public function make(array $data, array $rules, array $messages = []): self
    {
        $this->data = $data;
        $this->rules = $this->parseRules($rules);
        $this->messages = $messages;
        $this->errors = [];

        $this->validate();

        return $this;
    }

    /**
     * Проверяет, прошла ли валидация успешно
     *
     * @return bool
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Проверяет, есть ли ошибки валидации
     *
     * @return bool
     */
    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * Получает ошибки валидации
     *
     * @return array
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Получает первую ошибку для указанного атрибута
     *
     * @param string $attribute
     * @return string|null
     */
    public function first(string $attribute): ?string
    {
        return $this->errors[$attribute][0] ?? null;
    }

    /**
     * Добавляет кастомное правило валидации
     *
     * @param string $name Название правила
     * @param callable $callback Функция валидации
     * @return self
     */
    public function extend(string $name, callable $callback): self
    {
        $this->customRules[$name] = $callback;
        return $this;
    }

    /**
     * Выполняет валидацию данных
     *
     * @return void
     */
    protected function validate(): void
    {
        foreach ($this->rules as $attribute => $rules) {
            $value = $this->getValue($attribute);

            foreach ($rules as $rule) {
                $this->validateAttribute($attribute, $value, $rule);
            }
        }
    }

    /**
     * Валидирует атрибут по правилу
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $rule
     * @return void
     */
    protected function validateAttribute(string $attribute, $value, array $rule): void
    {
        $ruleName = $rule['name'];
        $parameters = $rule['parameters'];

        // Проверяем, требуется ли значение для правила required
        if ($ruleName === 'required' && !$this->validateRequired($value)) {
            $this->addError($attribute, $ruleName, $parameters);
            return;
        }

        // Если значение пустое и правило не required, пропускаем валидацию
        if ($this->isEmpty($value) && $ruleName !== 'required') {
            return;
        }

        // Выполняем валидацию
        if (!$this->validateRule($attribute, $value, $ruleName, $parameters)) {
            $this->addError($attribute, $ruleName, $parameters);
        }
    }

    /**
     * Проверяет правило валидации
     *
     * @param string $attribute
     * @param mixed $value
     * @param string $ruleName
     * @param array $parameters
     * @return bool
     */
    protected function validateRule(string $attribute, $value, string $ruleName, array $parameters): bool
    {
        // Проверяем кастомные правила
        if (isset($this->customRules[$ruleName])) {
            return call_user_func($this->customRules[$ruleName], $attribute, $value, $parameters, $this);
        }

        // Стандартные правила
        $method = 'validate' . ucfirst($ruleName);
        if (method_exists($this, $method)) {
            return $this->$method($attribute, $value, $parameters);
        }

        // Правила через классы
        $ruleClass = $this->getRuleClass($ruleName);
        if ($ruleClass && class_exists($ruleClass)) {
            /** @var ValidationRuleInterface $rule */
            $rule = new $ruleClass();
            $rule->setParameters($parameters);
            return $rule->passes($attribute, $value, $parameters, $this);
        }

        return true;
    }

    /**
     * Получает класс правила по имени
     *
     * @param string $ruleName
     * @return string|null
     */
    protected function getRuleClass(string $ruleName): ?string
    {
        $ruleMap = [
            'required' => \Architect\Validation\Rules\RequiredRule::class,
            'email' => \Architect\Validation\Rules\EmailRule::class,
            'numeric' => \Architect\Validation\Rules\NumericRule::class,
            'integer' => \Architect\Validation\Rules\IntegerRule::class,
            'string' => \Architect\Validation\Rules\StringRule::class,
            'array' => \Architect\Validation\Rules\ArrayRule::class,
            'min' => \Architect\Validation\Rules\MinRule::class,
            'max' => \Architect\Validation\Rules\MaxRule::class,
            'size' => \Architect\Validation\Rules\SizeRule::class,
            'in' => \Architect\Validation\Rules\InRule::class,
            'not_in' => \Architect\Validation\Rules\NotInRule::class,
            'unique' => \Architect\Validation\Rules\UniqueRule::class,
            'exists' => \Architect\Validation\Rules\ExistsRule::class,
            'regex' => \Architect\Validation\Rules\RegexRule::class,
            'date' => \Architect\Validation\Rules\DateRule::class,
            'before' => \Architect\Validation\Rules\BeforeRule::class,
            'after' => \Architect\Validation\Rules\AfterRule::class,
        ];

        return $ruleMap[$ruleName] ?? null;
    }

    /**
     * Проверяет правило required
     *
     * @param mixed $value
     * @return bool
     */
    protected function validateRequired($value): bool
    {
        if (is_null($value)) {
            return false;
        }

        if (is_string($value) && trim($value) === '') {
            return false;
        }

        if (is_array($value) && count($value) === 0) {
            return false;
        }

        return true;
    }

    /**
     * Проверяет, пустое ли значение
     *
     * @param mixed $value
     * @return bool
     */
    protected function isEmpty($value): bool
    {
        return is_null($value) || (is_string($value) && trim($value) === '');
    }

    /**
     * Добавляет ошибку валидации
     *
     * @param string $attribute
     * @param string $rule
     * @param array $parameters
     * @return void
     */
    protected function addError(string $attribute, string $rule, array $parameters): void
    {
        $message = $this->getErrorMessage($attribute, $rule, $parameters);
        $this->errors[$attribute][] = new ValidationError($attribute, $message, $parameters);
    }

    /**
     * Получает сообщение об ошибке
     *
     * @param string $attribute
     * @param string $rule
     * @param array $parameters
     * @return string
     */
    protected function getErrorMessage(string $attribute, string $rule, array $parameters): string
    {
        // Проверяем кастомные сообщения
        $customKey = $attribute . '.' . $rule;
        if (isset($this->messages[$customKey])) {
            return $this->replacePlaceholders($this->messages[$customKey], $attribute, $rule, $parameters);
        }

        if (isset($this->messages[$rule])) {
            return $this->replacePlaceholders($this->messages[$rule], $attribute, $rule, $parameters);
        }

        // Получаем сообщение из локализации или конфигурации
        $message = $this->getDefaultMessage($rule);
        return $this->replacePlaceholders($message, $attribute, $rule, $parameters);
    }

    /**
     * Получает сообщение по умолчанию для правила
     *
     * @param string $rule
     * @return string
     */
    protected function getDefaultMessage(string $rule): string
    {
        // В реальной реализации здесь должна быть загрузка из конфигурации или локализации
        $messages = [
            'required' => 'Поле :attribute обязательно для заполнения.',
            'email' => 'Поле :attribute должно быть корректным email адресом.',
            'numeric' => 'Поле :attribute должно быть числом.',
            'integer' => 'Поле :attribute должно быть целым числом.',
            'string' => 'Поле :attribute должно быть строкой.',
            'array' => 'Поле :attribute должно быть массивом.',
            'min' => 'Поле :attribute должно быть не меньше :min.',
            'max' => 'Поле :attribute должно быть не больше :max.',
            'size' => 'Поле :attribute должно быть равным :size.',
            'in' => 'Поле :attribute должно быть одним из допустимых значений: :values.',
            'not_in' => 'Поле :attribute не должно быть одним из запрещенных значений: :values.',
            'unique' => 'Поле :attribute уже существует.',
            'exists' => 'Выбранное значение для поля :attribute не существует.',
            'regex' => 'Поле :attribute имеет неверный формат.',
            'date' => 'Поле :attribute должно быть корректной датой.',
            'before' => 'Поле :attribute должно быть датой до :date.',
            'after' => 'Поле :attribute должно быть датой после :date.',
        ];

        return $messages[$rule] ?? 'Поле :attribute не прошло валидацию.';
    }

    /**
     * Заменяет плейсхолдеры в сообщении
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array $parameters
     * @return string
     */
    protected function replacePlaceholders(string $message, string $attribute, string $rule, array $parameters): string
    {
        $replace = [
            ':attribute' => $this->getAttributeName($attribute),
        ];

        // Добавляем параметры как :param1, :param2 и т.д.
        foreach ($parameters as $key => $value) {
            $replace[':' . ($key + 1)] = $value;
            $replace[':' . $value] = $value; // Для обратной совместимости
        }

        // Специальные плейсхолдеры для некоторых правил
        if ($rule === 'min' || $rule === 'max' || $rule === 'size') {
            $replace[':min'] = $parameters[0] ?? '';
            $replace[':max'] = $parameters[0] ?? '';
            $replace[':size'] = $parameters[0] ?? '';
        } elseif ($rule === 'in' || $rule === 'not_in') {
            $replace[':values'] = implode(', ', $parameters);
        } elseif ($rule === 'before' || $rule === 'after') {
            $replace[':date'] = $parameters[0] ?? '';
        }

        return str_replace(array_keys($replace), array_values($replace), $message);
    }

    /**
     * Получает читаемое имя атрибута
     *
     * @param string $attribute
     * @return string
     */
    protected function getAttributeName(string $attribute): string
    {
        // В реальной реализации здесь должна быть загрузка из конфигурации
        $attributes = [
            'name' => 'имя',
            'email' => 'email',
            'password' => 'пароль',
            'password_confirmation' => 'подтверждение пароля',
        ];

        return $attributes[$attribute] ?? $attribute;
    }

    /**
     * Получает значение атрибута из данных
     *
     * @param string $attribute
     * @return mixed
     */
    protected function getValue(string $attribute)
    {
        return $this->data[$attribute] ?? null;
    }

    /**
     * Парсит правила валидации
     *
     * @param array $rules
     * @return array
     */
    protected function parseRules(array $rules): array
    {
        $parsed = [];

        foreach ($rules as $attribute => $ruleString) {
            $ruleList = is_string($ruleString) ? explode('|', $ruleString) : $ruleString;
            $parsed[$attribute] = [];

            foreach ($ruleList as $rule) {
                $parsed[$attribute][] = $this->parseRule($rule);
            }
        }

        return $parsed;
    }

    /**
     * Парсит одно правило
     *
     * @param string $rule
     * @return array
     */
    protected function parseRule(string $rule): array
    {
        $parts = explode(':', $rule, 2);
        $name = $parts[0];
        $parameters = [];

        if (isset($parts[1])) {
            $parameters = explode(',', $parts[1]);
            $parameters = array_map('trim', $parameters);
        }

        return [
            'name' => $name,
            'parameters' => $parameters,
        ];
    }

    /**
     * Бросает исключение, если валидация не прошла
     *
     * @throws ValidationException
     */
    public function validateOrFail(): void
    {
        if ($this->fails()) {
            throw new ValidationException($this->errors);
        }
    }
}
