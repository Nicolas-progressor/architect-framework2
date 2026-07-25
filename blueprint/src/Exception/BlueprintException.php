<?php

declare(strict_types=1);

namespace Blueprint\Engine\Exception;

use Exception;

/**
 * Blueprint Exception
 * 
 * Custom exception class for Blueprint template engine errors.
 * 
 * @package Blueprint\Engine\Exception
 */
class BlueprintException extends Exception
{
    protected ?string $templateName = null;
    protected ?int $lineNumber = null;
    protected ?string $snippet = null;

    /**
     * Constructor
     * 
     * @param string $message Error message
     * @param string|null $templateName Template name where error occurred
     * @param int|null $lineNumber Line number in template
     * @param string|null $snippet Code snippet for context
     * @param Exception|null $previous Previous exception
     */
    public function __construct(
        string $message,
        ?string $templateName = null,
        ?int $lineNumber = null,
        ?string $snippet = null,
        ?Exception $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        
        $this->templateName = $templateName;
        $this->lineNumber = $lineNumber;
        $this->snippet = $snippet;
    }

    /**
     * Get template name
     * 
     * @return string|null
     */
    public function getTemplateName(): ?string
    {
        return $this->templateName;
    }

    /**
     * Get line number
     * 
     * @return int|null
     */
    public function getLineNumber(): ?int
    {
        return $this->lineNumber;
    }

    /**
     * Get code snippet
     * 
     * @return string|null
     */
    public function getSnippet(): ?string
    {
        return $this->snippet;
    }

    /**
     * Get formatted error message
     * 
     * @return string
     */
    public function getFormattedMessage(): string
    {
        $message = $this->getMessage();
        
        if ($this->templateName) {
            $message = "Error in template \"{$this->templateName}\"" 
                . ($this->lineNumber ? " (line {$this->lineNumber})" : '') 
                . ":\n\n" . $message;
        }
        
        if ($this->snippet) {
            $message .= "\n\nTemplate code:\n" . $this->snippet;
        }
        
        return $message;
    }

    /**
     * Create syntax error
     * 
     * @param string $message Error message
     * @param string|null $templateName Template name
     * @param int|null $lineNumber Line number
     * @param string|null $snippet Code snippet
     * @return self
     */
    public static function syntaxError(
        string $message,
        ?string $templateName = null,
        ?int $lineNumber = null,
        ?string $snippet = null
    ): self {
        return new self(
            "Syntax error: {$message}",
            $templateName,
            $lineNumber,
            $snippet
        );
    }

    /**
     * Create runtime error
     * 
     * @param string $message Error message
     * @param string|null $templateName Template name
     * @param int|null $lineNumber Line number
     * @param string|null $snippet Code snippet
     * @return self
     */
    public static function runtimeError(
        string $message,
        ?string $templateName = null,
        ?int $lineNumber = null,
        ?string $snippet = null
    ): self {
        return new self(
            "Runtime error: {$message}",
            $templateName,
            $lineNumber,
            $snippet
        );
    }

    /**
     * Create loader error
     * 
     * @param string $message Error message
     * @param string|null $templateName Template name
     * @return self
     */
    public static function loaderError(
        string $message,
        ?string $templateName = null
    ): self {
        return new self(
            "Loader error: {$message}",
            $templateName
        );
    }
}
