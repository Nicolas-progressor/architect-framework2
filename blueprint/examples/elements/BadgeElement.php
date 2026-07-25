<?php

declare(strict_types=1);

namespace App\Elements;

use Blueprint\Engine\BaseElement;
use Blueprint\Engine\Blueprint;

/**
 * Badge Element Example
 * 
 * Example element with pure PHP rendering (no template).
 * Shows how to create simple element that generates HTML directly.
 * 
 * Usage:
 *   {{ element('badge', ['text' => 'New', 'type' => 'success']) }}
 *   {{ element('badge', ['Draft', 'type' => 'secondary']) }}
 */
class BadgeElement extends BaseElement
{
    protected string $name = 'badge';
    
    /**
     * No template - pure PHP rendering
     */
    protected ?string $template = null;
    
    /**
     * Render badge HTML
     */
    public function render(array $data, Blueprint $blueprint): string
    {
        $this->blueprint = $blueprint;
        $this->data = $data;
        
        $text = $this->get('text', $this->get(0, ''));
        $type = $this->get('type', 'primary');
        $pill = $this->get('pill', false);
        
        $classes = $this->classList([
            'badge' => true,
            "bg-{$type}" => true,
            'rounded-pill' => $pill,
        ]);
        
        return sprintf(
            '<span class="%s">%s</span>',
            $this->escape($classes),
            $this->escape($text)
        );
    }
}
