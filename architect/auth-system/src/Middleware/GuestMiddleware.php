<?php

declare(strict_types=1);

namespace Architect\Auth\Middleware;

use Architect\Auth\Contracts\AuthenticationInterface;

class GuestMiddleware
{
    public function __construct(
        private AuthenticationInterface $auth
    ) {}

    /**
     * Обработать запрос.
     *
     * @param callable $next Следующий обработчик
     * @return mixed
     */
    public function handle(callable $next)
    {
        // Если уже авторизован - редирект на главную
        if ($this->auth->isLoggedIn()) {
            return $this->redirectToHome();
        }

        return $next();
    }

    /**
     * Перенаправить на главную.
     */
    protected function redirectToHome(): void
    {
        header('Location: /');
        exit;
    }
}
