<?php

declare(strict_types=1);

namespace Architect\Services\App\Contracts;

class AppDescriptor
{
    public function __construct(
        private readonly string $name,
        private readonly string $folder,
        private readonly string $path,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getFolder(): string
    {
        return $this->folder;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function exists(): bool
    {
        return $this->path !== '' && is_dir($this->path);
    }

    public function getModulesPath(): string
    {
        return $this->path . 'modules/';
    }

    public function getTemplatePath(): string
    {
        return $this->path . 'template/';
    }

    public function getRoutesPath(): string
    {
        return $this->path . 'routes/';
    }

    public function getConfigPath(): string
    {
        return $this->path . 'config/';
    }

    public function getBootstrapPath(): string
    {
        return $this->path . 'appbootstrap.php';
    }

    public function getBootstrapClassName(): string
    {
        return '\\app\\' . strtolower($this->name) . '\\appbootstrap';
    }

    /**
     * @return array{name: string, folder: string, path: string|null}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'folder' => $this->folder,
            'path' => $this->exists() ? $this->path : null,
        ];
    }
}
