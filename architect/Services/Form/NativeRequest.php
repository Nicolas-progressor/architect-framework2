<?php

declare(strict_types=1);

namespace Architect\Services\Form;

use Architect\Services\Form\Interfaces\RequestInterface;
use Architect\Services\Form\Interfaces\SessionInterface;

/**
 * Class NativeRequest
 *
 * Реализация RequestInterface для работы с PHP-суперглобальными.
 */
class NativeRequest implements RequestInterface
{
    /**
     * Session interface
     */
    private ?SessionInterface $session = null;

    /**
     * Конструктор
     *
     * @param SessionInterface|null $session
     */
    public function __construct(?SessionInterface $session = null)
    {
        $this->session = $session;
    }

    /**
     * Получить данные из POST
     *
     * @param string|null $key Ключ
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function getPost(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }

    /**
     * Получить данные из GET
     *
     * @param string|null $key Ключ
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function getGet(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    /**
     * Проверить, был ли POST-запрос
     *
     * @return bool
     */
    public function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }

    /**
     * Проверить, был ли GET-запрос
     *
     * @return bool
     */
    public function isGet(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET';
    }

    /**
     * Получить сессию
     *
     * @return SessionInterface
     */
    public function getSession(): SessionInterface
    {
        if ($this->session === null) {
            $this->session = new NativeSession();
        }
        return $this->session;
    }

    /**
     * Получить все данные запроса
     *
     * @return array
     */
    public function all(): array
    {
        return array_merge($_GET, $_POST);
    }
}
