<?php

declare(strict_types=1);

namespace Blueprint\Engine;

use Blueprint\Engine\Exception\BlueprintException;

/**
 * Element Manager
 * 
 * Manages template elements for Blueprint.
 * Supports three types of elements:
 * 1. Template files (.blu in elements/ directory)
 * 2. Callback functions
 * 3. Element classes (implementing ElementInterface)
 * 
 * @package Blueprint\Engine
 */
class ElementManager
{
    /**
     * Registered callback elements
     * 
     * @var array<string, callable>
     */
    protected array $callbacks = [];
    
    /**
     * Registered class elements
     * 
     * @var array<string, class-string<ElementInterface>>
     */
    protected array $classes = [];
    
    /**
     * Element instances cache
     * 
     * @var array<string, ElementInterface>
     */
    protected array $instances = [];
    
    /**
     * Elements directories
     * 
     * @var array<string>
     */
    protected array $elementDirs = [];
    
    /**
     * Blueprint instance
     * 
     * @var Blueprint
     */
    protected Blueprint $blueprint;
    
    /**
     * Constructor
     * 
     * @param Blueprint $blueprint Blueprint instance
     */
    public function __construct(Blueprint $blueprint)
    {
        $this->blueprint = $blueprint;
    }
    
    /**
     * Add elements directory
     * 
     * @param string $directory Directory path
     * @return self
     */
    public function addDirectory(string $directory): self
    {
        if (is_dir($directory) && !in_array($directory, $this->elementDirs, true)) {
            $this->elementDirs[] = rtrim($directory, '/\\');
        }
        return $this;
    }
    
    /**
     * Set elements directories
     * 
     * @param array $directories Array of directory paths
     * @return self
     */
    public function setDirectories(array $directories): self
    {
        $this->elementDirs = [];
        foreach ($directories as $dir) {
            $this->addDirectory($dir);
        }
        return $this;
    }
    
    /**
     * Get elements directories
     * 
     * @return array
     */
    public function getDirectories(): array
    {
        return $this->elementDirs;
    }
    
    /**
     * Register callback element
     * 
     * @param string $name Element name
     * @param callable $callback Callback function: fn(array $data, Blueprint $bp): string
     * @return self
     */
    public function register(string $name, callable $callback): self
    {
        $this->callbacks[$name] = $callback;
        return $this;
    }
    
    /**
     * Register element class
     * 
     * @param string $name Element name
     * @param class-string<ElementInterface> $className Class name
     * @return self
     */
    public function registerClass(string $name, string $className): self
    {
        if (!class_exists($className)) {
            throw BlueprintException::loaderError(
                "Element class '{$className}' not found",
                $name
            );
        }
        
        if (!is_a($className, ElementInterface::class, true)) {
            throw BlueprintException::loaderError(
                "Element class '{$className}' must implement ElementInterface",
                $name
            );
        }
        
        $this->classes[$name] = $className;
        return $this;
    }
    
    /**
     * Register element instance
     * 
     * @param ElementInterface $element Element instance
     * @return self
     */
    public function registerInstance(ElementInterface $element): self
    {
        $this->instances[$element->getName()] = $element;
        return $this;
    }
    
    /**
     * Check if element exists
     * 
     * @param string $name Element name
     * @return bool
     */
    public function has(string $name): bool
    {
        // Check registered instances
        if (isset($this->instances[$name])) {
            return true;
        }
        
        // Check registered classes
        if (isset($this->classes[$name])) {
            return true;
        }
        
        // Check registered callbacks
        if (isset($this->callbacks[$name])) {
            return true;
        }
        
        // Check template files
        return $this->findTemplate($name) !== null;
    }
    
    /**
     * Render element
     * 
     * Supports three element types:
     * 1. PHP class with template (hasTemplate() = true) - renders .blu file
     * 2. PHP class without template (hasTemplate() = false) - pure PHP rendering
     * 3. Template file (.blu) - direct template rendering
     * 
     * @param string $name Element name
     * @param array $data Element data
     * @return string
     */
    public function render(string $name, array $data = []): string
    {
        // 1. Check registered instances
        if (isset($this->instances[$name])) {
            return $this->renderElement($this->instances[$name], $data);
        }
        
        // 2. Check registered classes
        if (isset($this->classes[$name])) {
            if (!isset($this->instances[$name])) {
                $className = $this->classes[$name];
                $this->instances[$name] = new $className();
            }
            return $this->renderElement($this->instances[$name], $data);
        }
        
        // 3. Check registered callbacks
        if (isset($this->callbacks[$name])) {
            return ($this->callbacks[$name])($data, $this->blueprint);
        }
        
        // 4. Check template files
        $templatePath = $this->findTemplate($name);
        if ($templatePath !== null) {
            return $this->blueprint->render($templatePath, $data);
        }
        
        // Element not found
        return $this->renderNotFound($name);
    }
    
    /**
     * Render element instance (supports template rendering)
     * 
     * @param ElementInterface $element Element instance
     * @param array $data Element data
     * @return string
     */
    protected function renderElement(ElementInterface $element, array $data): string
    {
        // Call render() first - it might return HTML directly
        $result = $element->render($data, $this->blueprint);
        
        // If element uses template and render() returned empty string
        if ($element->hasTemplate() && $result === '') {
            $template = $element->getTemplate();
            
            if ($template && $this->blueprint->exists($template)) {
                // Get prepared data from element
                $templateData = $element->getTemplateData($data, $this->blueprint);
                
                // Render template
                return $this->blueprint->render($template, $templateData);
            }
        }
        
        return $result;
    }
    
    /**
     * Find element template file
     * 
     * @param string $name Element name
     * @return string|null
     */
    protected function findTemplate(string $name): ?string
    {
        $extensions = $this->blueprint->getLoader()->getExtensions();
        
        foreach ($this->elementDirs as $dir) {
            foreach ($extensions as $ext) {
                $path = $dir . '/' . $name . $ext;
                if (file_exists($path)) {
                    // Return relative path for Loader
                    return $this->getRelativePath($path);
                }
            }
        }
        
        // Also check in main template paths under elements/ subdirectory
        foreach ($this->blueprint->getLoader()->getPaths() as $path) {
            foreach ($extensions as $ext) {
                $elementPath = $path . '/elements/' . $name . $ext;
                if (file_exists($elementPath)) {
                    return 'elements/' . $name;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Get relative path for Loader
     * 
     * @param string $absolutePath Absolute path
     * @return string
     */
    protected function getRelativePath(string $absolutePath): string
    {
        $paths = $this->blueprint->getLoader()->getPaths();
        
        foreach ($paths as $basePath) {
            if (str_starts_with($absolutePath, $basePath)) {
                return ltrim(substr($absolutePath, strlen($basePath)), '/\\');
            }
        }
        
        // Return as-is if no match
        return $absolutePath;
    }
    
    /**
     * Render "not found" placeholder
     * 
     * @param string $name Element name
     * @return string
     */
    protected function renderNotFound(string $name): string
    {
        if ($this->blueprint->isDebug()) {
            return "<!-- Element '{$name}' not found -->";
        }
        return '';
    }
    
    /**
     * Get all registered element names
     * 
     * @return array
     */
    public function getRegisteredNames(): array
    {
        $names = array_merge(
            array_keys($this->instances),
            array_keys($this->classes),
            array_keys($this->callbacks)
        );
        
        // Add template-based elements
        foreach ($this->elementDirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            
            $extensions = $this->blueprint->getLoader()->getExtensions();
            foreach ($extensions as $ext) {
                $files = glob($dir . '/*' . $ext);
                foreach ($files as $file) {
                    $name = basename($file, $ext);
                    if (!in_array($name, $names, true)) {
                        $names[] = $name;
                    }
                }
            }
        }
        
        return $names;
    }
    
    /**
     * Clear all registered elements
     * 
     * @return self
     */
    public function clear(): self
    {
        $this->callbacks = [];
        $this->classes = [];
        $this->instances = [];
        return $this;
    }
    
    /**
     * Remove specific element
     * 
     * @param string $name Element name
     * @return self
     */
    public function remove(string $name): self
    {
        unset($this->callbacks[$name], $this->classes[$name], $this->instances[$name]);
        return $this;
    }
}
