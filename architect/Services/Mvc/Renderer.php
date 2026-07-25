<?php

declare(strict_types=1);

namespace Architect\Services\Mvc;

use Architect\Core\Contracts\ContainerInterface;
use Architect\Services\Mvc\Contracts\ResponseInterface;
use Architect\Services\Mvc\Context\MvcContext;

/**
 * Response Renderer.
 * 
 * Handles response output on render stage.
 * Supports JSON, HTML, and redirect responses.
 * 
 * @package Architect\Services\Mvc
 */
class Renderer
{
    /** @var ContainerInterface */
    private ContainerInterface $container;

    /** @var MvcContext */
    private MvcContext $context;

    /** @var bool Whether response was already sent */
    private bool $responseSent = false;

    /**
     * Create renderer instance.
     * 
     * @param ContainerInterface $container
     * @param MvcContext $context
     */
    public function __construct(ContainerInterface $container, MvcContext $context)
    {
        $this->container = $container;
        $this->context = $context;
    }

    /**
     * Render response.
     * 
     * Determines response type and outputs accordingly.
     */
    public function render(): void
    {
        if ($this->responseSent) {
            return;
        }

        // Skip if 404 error
        if ($this->context->is404Error()) {
            return;
        }

        $response = $this->getResponse();

        // Handle different response types
        match ($response->getType()) {
            'json' => $this->renderJson($response),
            'redirect' => $this->renderRedirect($response),
            'text' => $this->renderText($response),
            default => $this->renderHtml($response),
        };

        $this->responseSent = true;
    }

    /**
     * Render JSON response.
     * 
     * @param ResponseInterface $response
     */
    protected function renderJson(ResponseInterface $response): void
    {
        // Disable template for JSON
        $template = $this->container->get('template');
        $template->disable();

        // Set JSON content type if not set
        if (!$response->hasHeader('Content-Type')) {
            header('Content-Type: application/json');
        }

        $response->send();
    }

    /**
     * Render HTML response.
     * 
     * @param ResponseInterface $response
     */
    protected function renderHtml(ResponseInterface $response): void
    {
        $template = $this->container->get('template');

        // Check if response has content
        $content = $response->getContent();
        
        if (!empty($content)) {
            $template->setContent($content);
        }

        // Render template (which outputs content)
        $template->render();

        // Render debug panel if template is enabled
        if ($template->isEnabled()) {
            $debug = $this->container->get('debug');
            $debug->render();
        }
    }

    /**
     * Render redirect response.
     * 
     * @param ResponseInterface $response
     */
    protected function renderRedirect(ResponseInterface $response): void
    {
        // Redirects should already have headers sent
        // But send them if not
        if (!headers_sent()) {
            $response->send();
        }
    }

    /**
     * Render text response.
     * 
     * @param ResponseInterface $response
     */
    protected function renderText(ResponseInterface $response): void
    {
        // Disable template for plain text
        $template = $this->container->get('template');
        $template->disable();

        // Set text content type if not set
        if (!$response->hasHeader('Content-Type')) {
            header('Content-Type: text/plain; charset=utf-8');
        }

        $response->send();
    }

    /**
     * Get response from container.
     * 
     * @return ResponseInterface
     */
    protected function getResponse(): ResponseInterface
    {
        return $this->container->get('response');
    }

    /**
     * Check if response was already sent.
     * 
     * @return bool
     */
    public function isResponseSent(): bool
    {
        return $this->responseSent;
    }

    /**
     * Reset renderer state.
     */
    public function reset(): void
    {
        $this->responseSent = false;
    }
}
