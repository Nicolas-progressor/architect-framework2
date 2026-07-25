<?php

declare(strict_types=1);

namespace Blueprint\Engine\Functions;

/**
 * Date Functions
 * 
 * Functions for date/time operations.
 * 
 * @package Blueprint\Engine\Functions
 */
class DateFunctions
{
    /**
     * Get all date functions
     * 
     * @return array<string, callable>
     */
    public static function getFunctions(): array
    {
        return [
            'now' => [self::class, 'now'],
            'time' => [self::class, 'time'],
            'date' => [self::class, 'date'],
            'strtotime' => [self::class, 'strtotime'],
            'mktime' => [self::class, 'mktime'],
            'checkdate' => [self::class, 'checkdate'],
            'date_create' => [self::class, 'dateCreate'],
            'date_format' => [self::class, 'dateFormat'],
            'date_modify' => [self::class, 'dateModify'],
            'date_diff' => [self::class, 'dateDiff'],
        ];
    }

    /**
     * Current timestamp
     */
    public static function now(): int
    {
        return time();
    }

    /**
     * Current timestamp (alias)
     */
    public static function time(): int
    {
        return time();
    }

    /**
     * Format date
     */
    public static function date(string $format, ?int $timestamp = null): string
    {
        return date($format, $timestamp ?? time());
    }

    /**
     * String to time
     */
    public static function strtotime(string $time, ?int $now = null): int|false
    {
        return strtotime($time, $now ?? time());
    }

    /**
     * Make timestamp
     */
    public static function mktime(
        int $hour = 0,
        int $minute = 0,
        int $second = 0,
        ?int $month = null,
        ?int $day = null,
        ?int $year = null
    ): int {
        return mktime($hour, $minute, $second, $month, $day, $year);
    }

    /**
     * Check date validity
     */
    public static function checkdate(int $month, int $day, int $year): bool
    {
        return checkdate($month, $day, $year);
    }

    /**
     * Create DateTime object
     */
    public static function dateCreate(string $time = 'now'): \DateTime|false
    {
        return date_create($time);
    }

    /**
     * Format DateTime
     */
    public static function dateFormat(\DateTimeInterface $date, string $format): string
    {
        return $date->format($format);
    }

    /**
     * Modify DateTime
     */
    public static function dateModify(\DateTime $date, string $modify): \DateTime|false
    {
        return $date->modify($modify);
    }

    /**
     * Date difference
     */
    public static function dateDiff(\DateTimeInterface $date1, \DateTimeInterface $date2): \DateInterval
    {
        return $date1->diff($date2);
    }
}
