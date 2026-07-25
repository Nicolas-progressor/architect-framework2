<?php

declare(strict_types=1);

namespace Architect\Auth\Middleware;

use Architect\Auth\Contracts\AuthenticationInterface;
use Architect\Auth\Contracts\AuthorizationInterface;

class RoleMiddleware
{
    public function __construct(
        private AuthenticationInterface $auth,
        private AuthorizationInterface $authorization
    ) {}

    /**
     * Обработать запрос.
     *
     * @param callable $next Следующий обработчик
     * @param string|array $roles Роль или массив ролей
     * @return mixed
     */
    public function handle(callable $next, string|array $roles = [])
    {
        // Сначала проверяем авторизацию
        if (!$this->auth->isLoggedIn()) {
            return $this->redirectToLogin();
        }

        $user = $this->auth->getUser();
        if (!$user) {
            return $this->redirectToLogin();
        }

        // Преобразуем в массив
        $roles = is_array($roles) ? $roles : [$roles];

        // Проверяем роль
        foreach ($roles as $role) {
            if ($this->authorization->hasRole($user, $role)) {
                return $next();
            }
        }

        // Нет нужной роли
        return $this->denyAccess('Доступ разрешён только для роли: ' . implode(', ', $roles));
    }

    /**
     * Перенаправить на страницу входа.
     */
    protected function redirectToLogin(): void
    {
        $loginUrl = '/login?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/');
        header('Location: ' . $loginUrl);
        exit;
    }

    /**
     * Запретить доступ.
     */
    protected function denyAccess(string $message = 'Доступ запрещён'): void
    {
        http_response_code(403);
        echo '<h1>403 Forbidden</h1>';
        echo '<p>' . htmlspecialchars($message) . '</p>';
        exit;
    }
}
