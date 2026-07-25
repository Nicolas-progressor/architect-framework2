<?php

declare(strict_types=1);

namespace Architect\Services\Blueprint\Filters;

use Architect\Services\Blueprint\Contracts\FilterRegistryInterface;

/**
 * Default Blueprint filters (plural, etc.)
 */
final class DefaultFilters implements FilterRegistryInterface
{
    public function register(callable $registrar): void
    {
        foreach ($this->getFilters() as $name => $callback) {
            $registrar($name, $callback);
        }
    }

    public function getFilters(): array
    {
        return [
            'plural' => fn(int $count, string $forms): string => $this->plural($count, $forms),
        ];
    }

    /**
     * Russian pluralization
     * 
     * @param int $count Count
     * @param string $forms Forms separated by | (e.g., "товар|товара|товаров")
     */
    private function plural(int $count, string $forms): string
    {
        $parts = explode('|', $forms);
        $n = abs($count) % 100;
        $n1 = $n % 10;

        if ($n > 10 && $n < 20) {
            return $count . ' ' . ($parts[2] ?? $parts[0]);
        }
        
        if ($n1 > 1 && $n1 < 5) {
            return $count . ' ' . ($parts[1] ?? $parts[0]);
        }
        
        if ($n1 === 1) {
            return $count . ' ' . $parts[0];
        }

        return $count . ' ' . ($parts[2] ?? $parts[0]);
    }
}
