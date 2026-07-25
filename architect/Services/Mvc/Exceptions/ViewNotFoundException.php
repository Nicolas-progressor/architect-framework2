<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Exceptions;

/**
 * Exception thrown when view template cannot be found.
 * 
 * Thrown when the specified template file does not exist
 * in the template directory.
 * 
 * @package Architect\Services\Mvc\Exceptions
 */
class ViewNotFoundException extends MvcException
{
    /** @var string Template name */
    private string $template;

    /** @var string Template directory path */
    private string $templateDir;

    /**
     * Create exception for missing view.
     * 
     * @param string $template Template name
     * @param string $templateDir Template directory path
     * @return self
     */
    public static function create(string $template, string $templateDir): self
    {
        $exception = new self("View template not found: {$template}");
        $exception->template = $template;
        $exception->templateDir = $templateDir;

        return $exception;
    }

    /**
     * Get template name.
     * 
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * Get template directory.
     * 
     * @return string
     */
    public function getTemplateDir(): string
    {
        return $this->templateDir;
    }
}
