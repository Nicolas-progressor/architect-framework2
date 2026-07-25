<?php

declare(strict_types=1);

namespace Blueprint\Engine\Runtime;

/**
 * Property Accessor
 * 
 * Handles accessing properties on objects and arrays.
 * Supports property access, getter methods, and magic methods.
 * 
 * @package Blueprint\Engine\Runtime
 */
class PropertyAccessor
{
    /**
     * Get property value from object or array
     * 
     * @param mixed $object Object or array
     * @param string $property Property name
     * @return mixed
     */
    public static function get(mixed $object, string $property): mixed
    {
        if ($object === null) {
            return null;
        }

        // Array access
        if (is_array($object)) {
            return $object[$property] ?? null;
        }

        // Object access
        if (is_object($object)) {
            return self::getObjectProperty($object, $property);
        }

        return null;
    }

    /**
     * Get object property using various strategies
     * 
     * @param object $object Object
     * @param string $property Property name
     * @return mixed
     */
    protected static function getObjectProperty(object $object, string $property): mixed
    {
        // 1. Check public property
        if (isset($object->$property)) {
            return $object->$property;
        }

        // 2. Check getter method
        $getter = 'get' . ucfirst($property);
        if (method_exists($object, $getter)) {
            return $object->$getter();
        }

        // 3. Check isser method (for boolean properties)
        $isser = 'is' . ucfirst($property);
        if (method_exists($object, $isser)) {
            return $object->$isser();
        }

        // 4. Check hasser method (for has* methods)
        $hasser = 'has' . ucfirst($property);
        if (method_exists($object, $hasser)) {
            return $object->$hasser();
        }

        // 5. Check magic __get
        if (method_exists($object, '__get')) {
            return $object->$property;
        }

        // 6. Check ArrayAccess
        if ($object instanceof \ArrayAccess) {
            return $object[$property] ?? null;
        }

        return null;
    }

    /**
     * Check if property exists
     * 
     * @param mixed $object Object or array
     * @param string $property Property name
     * @return bool
     */
    public static function has(mixed $object, string $property): bool
    {
        if ($object === null) {
            return false;
        }

        if (is_array($object)) {
            return array_key_exists($property, $object);
        }

        if (is_object($object)) {
            // Public property
            if (property_exists($object, $property)) {
                return true;
            }

            // Getter
            if (method_exists($object, 'get' . ucfirst($property))) {
                return true;
            }

            // Isser
            if (method_exists($object, 'is' . ucfirst($property))) {
                return true;
            }

            // Magic method
            if (method_exists($object, '__get')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get nested property using dot notation
     * 
     * @param mixed $object Object or array
     * @param string $path Dot-separated path (e.g., "user.profile.name")
     * @param mixed $default Default value
     * @return mixed
     */
    public static function getNested(mixed $object, string $path, mixed $default = null): mixed
    {
        $keys = explode('.', $path);
        $value = $object;

        foreach ($keys as $key) {
            if ($value === null) {
                return $default;
            }

            $value = self::get($value, $key);
        }

        return $value ?? $default;
    }
}
