<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Middleware\Adapters;

use Architect\Auth\AuthManager;
use Architect\Services\Mvc\Http\Response;
use Architect\Services\Mvc\Middleware\BaseMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * PSR-15 Adapter for Architect Auth System.
 * 
 * Wraps existing AuthManager into PSR-15 middleware.
 * 
 * @package Architect\Services\Mvc\Middleware\Adapters
 */
class AuthAdapter extends BaseMiddleware
{
    /** @var AuthManager|null Auth manager instance */
    protected ?AuthManager $authManager = null;

    /** @var string|null Redirect URL for unauthenticated users */
    protected ?string $loginUrl = '/login';

    /** @var string|null Required permission */
    protected ?string $permission = null;

    /** @var string|null Required role */
    protected ?string $role = null;

    /**
     * @inheritdoc
     */
    public function process(ServerRequestInterface $request, $handler): ResponseInterface
    {
        $auth = $this->getAuthManager();

        if (!$auth->isLoggedIn()) {
            return $this->unauthenticated($request);
        }

        // Check permission if specified
        if ($this->permission !== null && !$auth->hasPermission($this->permission)) {
            return $this->forbidden('Permission denied');
        }

        // Check role if specified
        if ($this->role !== null && !$auth->hasRole($this->role)) {
            return $this->forbidden('Role required');
        }

        return $handler->handle($request);
    }

    /**
     * Get AuthManager instance.
     * 
     * @return AuthManager
     */
    protected function getAuthManager(): AuthManager
    {
        if ($this->authManager === null) {
            $this->authManager = $this->container !== null
                ? $this->container->get('auth')
                : new AuthManager();
        }

        return $this->authManager;
    }

    /**
     * Handle unauthenticated request.
     * 
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    protected function unauthenticated(ServerRequestInterface $request): ResponseInterface
    {
        $redirectUrl = $this->loginUrl . '?redirect=' . urlencode($request->getUri()->getPath());
        return Response::redirect($redirectUrl);
    }

    /**
     * Handle forbidden access.
     * 
     * @param string $message
     * @return ResponseInterface
     */
    protected function forbidden(string $message = 'Forbidden'): ResponseInterface
    {
        return Response::json([
            'error' => 'Forbidden',
            'message' => $message,
        ], 403);
    }

    /**
     * Set login URL.
     * 
     * @param string $url Login URL
     * @return self
     */
    public function setLoginUrl(string $url): self
    {
        $this->loginUrl = $url;
        return $this;
    }

    /**
     * Set required permission.
     * 
     * @param string $permission Permission name
     * @return self
     */
    public function setPermission(string $permission): self
    {
        $this->permission = $permission;
        return $this;
    }

    /**
     * Set required role.
     * 
     * @param string $role Role name
     * @return self
     */
    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    /**
     * Set AuthManager instance.
     * 
     * @param AuthManager $authManager
     * @return self
     */
    public function setAuthManager(AuthManager $authManager): self
    {
        $this->authManager = $authManager;
        return $this;
    }
}
