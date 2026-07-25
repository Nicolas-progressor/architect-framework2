<?php

declare(strict_types=1);

namespace Blueprint\Engine\Runtime;

/**
 * Method Caller
 * 
 * Handles method calls on objects within templates.
 * 
 * @package Blueprint\Engine\Runtime
 */
class MethodCaller
{
    /**
     * Call method on object
     * 
     * @param mixed $object Object to call method on
     * @param string $method Method name
     * @param array $args Method arguments
     * @return mixed
     */
    public static function call(mixed $object, string $method, array $args = []): mixed
    {
        if ($object === null) {
            return null;
        }

        if (!is_object($object)) {
            return null;
        }

        // Check if method exists
        if (!method_exists($object, $method)) {
            // Check magic __call
            if (method_exists($object, '__call')) {
                return $object->$method(...$args);
            }
            return null;
        }

        return $object->$method(...$args);
    }

    /**
     * Check if method exists on object
     * 
     * @param mixed $object Object
     * @param string $method Method name
     * @return bool
     */
    public static function hasMethod(mixed $object, string $method): bool
    {
        if (!is_object($object)) {
            return false;
        }

        if (method_exists($object, $method)) {
            return true;
        }

        if (method_exists($object, '__call')) {
            return true;
        }

        return false;
    }

    /**
     * Call method with named arguments
     * 
     * @param mixed $object Object to call method on
     * @param string $method Method name
     * @param array $namedArgs Named arguments
     * @return mixed
     */
    public static function callNamed(mixed $object, string $method, array $namedArgs = []): mixed
    {
        if ($object === null || !is_object($object)) {
            return null;
        }

        if (!method_exists($object, $method)) {
            return null;
        }

        // Get method reflection
        try {
            $reflection = new \ReflectionMethod($object, $method);
            $parameters = $reflection->getParameters();
            
            $args = [];
            foreach ($parameters as $param) {
                $name = $param->getName();
                
                if (array_key_exists($name, $namedArgs)) {
                    $args[] = $namedArgs[$name];
                } elseif ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                } else {
                    $args[] = null;
                }
            }

            return $object->$method(...$args);
        } catch (\ReflectionException $e) {
            return null;
        }
    }
}
