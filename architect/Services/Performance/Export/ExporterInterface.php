<?php

declare(strict_types=1);

namespace Architect\Services\Performance\Export;

interface ExporterInterface
{
    public function export(array $metrics, array $options = []): ExportResult;
    
    public function getFormat(): string;
    
    public function getMimeType(): string;
    
    public function getFileExtension(): string;
}