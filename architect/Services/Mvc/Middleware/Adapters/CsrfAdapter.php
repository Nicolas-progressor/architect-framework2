<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Middleware\Adapters;

use Architect\Services\Form\CSRFTokenManager;
use Architect\Services\Mvc\Http\Response;
use Architect\Services\Mvc\Middleware\BaseMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * PSR-15 Adapter for CSRF Token Manager.
 *
 * Wraps existing CSRFTokenManager into PSR-15 middleware.
 *
 * @package Architect\Services\Mvc\Middleware\Adapters
 */
class CsrfAdapter extends BaseMiddleware
{
    /** @var CSRFTokenManager|null CSRF manager instance */
    protected ?CSRFTokenManager $csrfManager = null;

    /** @var string Form name for token */
    protected string $formName = 'default';

    /** @var string Token field name in request */
    protected string $tokenField = 'csrf_token';

    /** @var array<string> Methods that require CSRF validation */
    protected array $protectedMethods = ['POST', 'PUT', 'DELETE', 'PATCH'];

    /**
     * @inheritdoc
     */
    public function process(ServerRequestInterface $request, $handler): ResponseInterface
    {
        $csrf = $this->getCsrfManager();

        // Generate and attach token to request
        $token = $csrf->generateToken($this->formName);
        $request = $request->withAttribute('csrf_token', $token);
        $request = $request->withAttribute('csrf_field', $csrf->getTokenField($this->formName));

        // Skip validation for non-protected methods
        if (!in_array($request->getMethod(), $this->protectedMethods, true)) {
            return $handler->handle($request);
        }

        // Get token from request
        $requestToken = $this->getTokenFromRequest($request);

        // Validate token
        if (!$csrf->validateToken($this->formName, $requestToken)) {
            return $this->invalidToken();
        }

        return $handler->handle($request);
    }

    /**
     * Get CSRFTokenManager instance.
     *
     * @return CSRFTokenManager
     */
    protected function getCsrfManager(): CSRFTokenManager
    {
        if ($this->csrfManager === null) {
            $this->csrfManager = $this->container !== null && $this->container->has('form')
                ? $this->container->get('form')->getCsrfManager()
                : new CSRFTokenManager();
        }

        return $this->csrfManager;
    }

    /**
     * Get token from request.
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    protected function getTokenFromRequest(ServerRequestInterface $request): string
    {
        // Check header first
        $headerToken = $request->getHeaderLine('X-CSRF-Token');
        if (!empty($headerToken)) {
            return $headerToken;
        }

        // Check parsed body
        $body = $request->getParsedBody();
        if (is_array($body) && isset($body[$this->tokenField])) {
            return (string) $body[$this->tokenField];
        }

        // Check query params
        $query = $request->getQueryParams();
        if (isset($query[$this->tokenField])) {
            return (string) $query[$this->tokenField];
        }

        return '';
    }

    /**
     * Handle invalid token.
     *
     * @return ResponseInterface
     */
    protected function invalidToken(): ResponseInterface
    {
        return Response::json([
            'error' => 'CSRF Token Mismatch',
            'message' => 'Invalid or missing CSRF token',
        ], 419);
    }

    /**
     * Set form name.
     *
     * @param string $name Form name
     * @return self
     */
    public function setFormName(string $name): self
    {
        $this->formName = $name;
        return $this;
    }

    /**
     * Set token field name.
     *
     * @param string $field Field name
     * @return self
     */
    public function setTokenField(string $field): self
    {
        $this->tokenField = $field;
        return $this;
    }

    /**
     * Set CSRFTokenManager instance.
     *
     * @param CSRFTokenManager $manager
     * @return self
     */
    public function setCsrfManager(CSRFTokenManager $manager): self
    {
        $this->csrfManager = $manager;
        return $this;
    }

    /**
     * Get hidden input field HTML.
     *
     * @return string
     */
    public function getTokenField(): string
    {
        return $this->getCsrfManager()->getTokenField($this->formName);
    }

    /**
     * Get meta tag HTML.
     *
     * @return string
     */
    public function getMetaTag(): string
    {
        return $this->getCsrfManager()->getMetaTag($this->formName);
    }
}
