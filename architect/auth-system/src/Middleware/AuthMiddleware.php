<?php

declare(strict_types=1);

namespace Architect\Auth\Middleware;

use Architect\Auth\Contracts\AuthenticationInterface;
use Architect\Auth\Contracts\AuthorizationInterface;

class AuthMiddleware
{
    public function __construct(
        private AuthenticationInterface $auth,
        private AuthorizationInterface $authorization
    ) {}

    /**
     * Обработать запрос.
     *
     * @param callable $next Следующий обработчик
     * @param array $params Дополнительные параметры (permission, role)
     * @return mixed
     */
    public function handle(callable $next, array $params = [])
    {
        if (!$this->auth->isLoggedIn()) {
            return $this->redirectToLogin();
        }

        $user = $this->auth->getUser();
        if (!$user) {
            return $this->redirectToLogin();
        }

        // Проверка разрешений если указаны
        if (!empty($params['permission'])) {
            if (!$this->authorization->hasPermission($user, $params['permission'])) {
                return $this->denyAccess('У вас нет разрешения для этого действия');
            }
        }

        // Проверка роли если указана
        if (!empty($params['role'])) {
            if (!$this->authorization->hasRole($user, $params['role'])) {
                return $this->denyAccess('У вас нет доступа к этой странице');
            }
        }

        return $next();
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
