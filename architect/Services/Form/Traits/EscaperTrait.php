<?php

declare(strict_types=1);

namespace Architect\Services\Form\Traits;

/**
 * Trait EscaperTrait
 * 
 * Предоставляет метод для экранирования HTML-сущностей.
 */
trait EscaperTrait
{
    /**
     * Экранировать строку для безопасного вывода в HTML.
     * 
     * @param string $value
     * @return string
     */
    protected function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}