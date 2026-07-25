<?php

declare(strict_types=1);

namespace App\Elements;

use Blueprint\Engine\BaseElement;
use Blueprint\Engine\Blueprint;

/**
 * Card Element Example
 * 
 * Example element with both rendering modes.
 * Uncomment $template to use template instead of pure PHP.
 * 
 * Usage:
 *   {!! element('card', ['title' => 'Title', 'body' => 'Content']) !!}
 *   {% element 'card' with {title: 'Title', body: 'Content', image: '/img.jpg'} %}
 */
class CardElement extends BaseElement
{
    protected string $name = 'card';
    
    /**
     * Template path - uncomment to use template:
     * protected ?string $template = 'elements/card';
     */
    protected ?string $template = null;
    
    /**
     * Render card HTML (pure PHP mode)
     */
    public function render(array $data, Blueprint $blueprint): string
    {
        $this->blueprint = $blueprint;
        $this->data = $data;
        
        // If using template, return empty string
        if ($this->hasTemplate()) {
            return '';
        }
        
        return $this->renderPhp();
    }
    
    /**
     * Prepare data for template
     */
    public function getTemplateData(array $data, Blueprint $blueprint): array
    {
        return [
            'title' => $data['title'] ?? null,
            'body' => $data['body'] ?? $data[0] ?? '',
            'footer' => $data['footer'] ?? null,
            'image' => $data['image'] ?? null,
            'image_alt' => $data['image_alt'] ?? '',
            'class' => $data['class'] ?? '',
        ];
    }
    
    /**
     * Pure PHP rendering
     */
    protected function renderPhp(): string
    {
        $title = $this->get('title');
        $body = $this->get('body', $this->get(0, ''));
        $footer = $this->get('footer');
        $image = $this->get('image');
        $imageAlt = $this->get('image_alt', '');
        $class = $this->get('class', '');
        
        $html = '<div class="card ' . $this->escape($class) . '">';
        
        if ($image) {
            $html .= '<img src="' . $this->escape($image) . '" class="card-img-top" alt="' . $this->escape($imageAlt) . '">';
        }
        
        if ($title) {
            $html .= '<div class="card-header">';
            $html .= '<h5 class="card-title">' . $this->escape($title) . '</h5>';
            $html .= '</div>';
        }
        
        $html .= '<div class="card-body">';
        $html .= $body; // Allow HTML
        $html .= '</div>';
        
        if ($footer) {
            $html .= '<div class="card-footer">';
            $html .= $this->escape($footer);
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
