<?php

declare(strict_types=1);

namespace App\Elements;

use Blueprint\Engine\BaseElement;
use Blueprint\Engine\Blueprint;

/**
 * Alert Element Example
 * 
 * Example element with template rendering.
 * Shows how to create element that uses .blu template.
 * 
 * Usage:
 *   {!! element('alert', ['type' => 'success', 'message' => 'Saved!']) !!}
 *   {% element 'alert' with {type: 'danger', message: 'Error!'} %}
 */
class AlertElement extends BaseElement
{
    protected string $name = 'alert';
    
    /**
     * Template path (relative to template paths)
     * Set to null for pure PHP rendering
     */
    protected ?string $template = 'elements/alert';
    
    /**
     * Prepare data for template
     */
    public function getTemplateData(array $data, Blueprint $blueprint): array
    {
        return [
            'type' => $data['type'] ?? 'info',
            'message' => $data['message'] ?? '',
            'dismissible' => $data['dismissible'] ?? false,
            'title' => $data['title'] ?? null,
        ];
    }
}
