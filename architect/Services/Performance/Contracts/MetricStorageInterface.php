<?php

declare(strict_types=1);

namespace Architect\Services\Performance\Contracts;

interface MetricStorageInterface
{
    public function store(array $metrics): bool;
    
    public function retrieve(string $id): ?array;
    
    public function list(int $limit = 100, int $offset = 0): array;
    
    public function delete(string $id): bool;
    
    public function clear(): bool;
    
    public function getStorageSize(): int;
}