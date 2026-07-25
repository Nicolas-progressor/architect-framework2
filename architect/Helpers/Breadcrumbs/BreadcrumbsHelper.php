<?php

declare(strict_types=1);

namespace Architect\Helpers\Breadcrumbs;

use Architect\Core\Contracts\ContainerInterface;
use Architect\Helpers\Core\AbstractHelper;

/**
 * Breadcrumbs helper for navigation breadcrumbs.
 */
class BreadcrumbsHelper extends AbstractHelper
{
    /** @var array Breadcrumb items */
    private array $crumbs = [];

    /**
     * Create Breadcrumbs helper with container.
     */
    public function __construct(ContainerInterface $container)
    {
        // Container available for future extensions
    }

    /**
     * Add breadcrumb item.
     */
    public function add(string $title, ?string $url = null, bool $active = false): self
    {
        $this->crumbs[] = [
            'title' => $title,
            'url' => $url,
            'active' => $active,
        ];
        return $this;
    }

    /**
     * Get all breadcrumbs.
     */
    public function all(): array
    {
        return $this->crumbs;
    }

    /**
     * Clear all breadcrumbs.
     */
    public function clear(): self
    {
        $this->crumbs = [];
        return $this;
    }

    /**
     * Render breadcrumbs HTML.
     */
    public function render(): string
    {
        if (empty($this->crumbs)) {
            return '';
        }

        $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';

        foreach ($this->crumbs as $crumb) {
            if ($crumb['active']) {
                $html .= '<li class="breadcrumb-item active" aria-current="page">';
                $html .= htmlspecialchars($crumb['title']) . '</li>';
            } else {
                $html .= '<li class="breadcrumb-item">';
                if ($crumb['url']) {
                    $html .= '<a href="' . htmlspecialchars($crumb['url']) . '">';
                    $html .= htmlspecialchars($crumb['title']) . '</a>';
                } else {
                    $html .= htmlspecialchars($crumb['title']);
                }
                $html .= '</li>';
            }
        }

        $html .= '</ol></nav>';

        return $html;
    }
}
