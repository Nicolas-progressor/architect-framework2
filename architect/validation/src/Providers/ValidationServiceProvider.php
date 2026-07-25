<?php

declare(strict_types=1);

namespace Architect\Validation\Providers;

use Architect\Contracts\ServiceProviderInterface;
use Architect\Core\Contracts\ContainerInterface;
use Architect\Validation\Rules\AfterRule;
use Architect\Validation\Rules\ArrayRule;
use Architect\Validation\Rules\BeforeRule;
use Architect\Validation\Rules\DateRule;
use Architect\Validation\Rules\EmailRule;
use Architect\Validation\Rules\ExistsRule;
use Architect\Validation\Rules\InRule;
use Architect\Validation\Rules\IntegerRule;
use Architect\Validation\Rules\MaxRule;
use Architect\Validation\Rules\MinRule;
use Architect\Validation\Rules\NotInRule;
use Architect\Validation\Rules\NumericRule;
use Architect\Validation\Rules\RegexRule;
use Architect\Validation\Rules\RequiredRule;
use Architect\Validation\Rules\Rule;
use Architect\Validation\Rules\SizeRule;
use Architect\Validation\Rules\StringRule;
use Architect\Validation\Rules\UniqueRule;
use Architect\Validation\Validator;

class ValidationServiceProvider implements ServiceProviderInterface
{
    /**
     * Регистрирует сервисы валидации в контейнере
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function register(ContainerInterface $container): void
    {
        $this->log('[ValidationServiceProvider] register called', $container);

        // Регистрируем валидатор как синглтон
        $container->singleton('validator', function ($container) {
            $validator = new Validator($container);
            $this->registerDefaultRules($validator);
            return $validator;
        });

        // Регистрируем фасад валидатора
        $container->alias(Validator::class, 'validator');

        $this->log('[ValidationServiceProvider] validator registered', $container);
    }

    /**
     * Загружает сервисы валидации после регистрации
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function boot(ContainerInterface $container): void
    {
        $this->log('[ValidationServiceProvider] boot called', $container);

        // Регистрируем кастомные правила, если они есть в конфигурации
        $this->registerCustomRules($container);
    }

    /**
     * Регистрирует стандартные правила валидации
     *
     * @param Validator $validator
     * @return void
     */
    protected function registerDefaultRules(Validator $validator): void
    {
        $rules = [
            'required' => RequiredRule::class,
            'email' => EmailRule::class,
            'numeric' => NumericRule::class,
            'integer' => IntegerRule::class,
            'string' => StringRule::class,
            'array' => ArrayRule::class,
            'min' => MinRule::class,
            'max' => MaxRule::class,
            'size' => SizeRule::class,
            'in' => InRule::class,
            'not_in' => NotInRule::class,
            'unique' => UniqueRule::class,
            'exists' => ExistsRule::class,
            'regex' => RegexRule::class,
            'date' => DateRule::class,
            'before' => BeforeRule::class,
            'after' => AfterRule::class,
        ];

        foreach ($rules as $name => $class) {
            $validator->extend($name, function ($attribute, $value, $parameters, $validator) use ($class) {
                /** @var Rule $rule */
                $rule = new $class();
                $rule->setParameters($parameters);
                return $rule->passes($attribute, $value, $parameters, $validator);
            });
        }
    }

    /**
     * Регистрирует кастомные правила из конфигурации
     *
     * @param ContainerInterface $container
     * @return void
     */
    protected function registerCustomRules(ContainerInterface $container): void
    {
        if (!$container->has('config')) {
            return;
        }

        $config = $container->get('config');
        $customRules = $config->get('validation.custom_rules', []);

        if (empty($customRules)) {
            return;
        }

        $validator = $container->get('validator');

        foreach ($customRules as $name => $ruleClass) {
            if (class_exists($ruleClass) && is_subclass_of($ruleClass, Rule::class)) {
                $validator->extend($name, function ($attribute, $value, $parameters, $validator) use ($ruleClass) {
                    /** @var Rule $rule */
                    $rule = new $ruleClass();
                    $rule->setParameters($parameters);
                    return $rule->passes($attribute, $value, $parameters, $validator);
                });

                $this->log("[ValidationServiceProvider] registered custom rule: {$name}", $container);
            }
        }
    }

    /**
     * Логирование сообщения
     *
     * @param string $message
     * @param ContainerInterface $container
     * @return void
     */
    private function log(string $message, ContainerInterface $container): void
    {
        if ($container->has('logger')) {
            $logger = $container->get('logger');
            $logger->debug($message);
        } else {
            error_log($message);
        }
    }
}
