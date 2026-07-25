<?php

declare(strict_types=1);

namespace Architect\Console;

/**
 * Console output formatter with ANSI colors and styling
 */
class OutputFormatter
{
    // ANSI color codes
    public const BLACK = '0;30';
    public const RED = '0;31';
    public const GREEN = '0;32';
    public const YELLOW = '0;33';
    public const BLUE = '0;34';
    public const MAGENTA = '0;35';
    public const CYAN = '0;36';
    public const WHITE = '0;37';

    // Bold colors
    public const BLACK_BOLD = '1;30';
    public const RED_BOLD = '1;31';
    public const GREEN_BOLD = '1;32';
    public const YELLOW_BOLD = '1;33';
    public const BLUE_BOLD = '1;34';
    public const MAGENTA_BOLD = '1;35';
    public const CYAN_BOLD = '1;36';
    public const WHITE_BOLD = '1;37';

    // Background colors
    public const BG_RED = '41';
    public const BG_GREEN = '42';
    public const BG_YELLOW = '43';
    public const BG_BLUE = '44';
    public const BG_MAGENTA = '45';
    public const BG_CYAN = '46';

    // Styles
    public const RESET = '0';
    public const BOLD = '1';
    public const UNDERLINE = '4';
    public const REVERSE = '7';

    protected bool $useColors = true;
    protected bool $isWindows = false;

    public function __construct()
    {
        $this->isWindows = $this->isWindows();
        $this->useColors = $this->shouldUseColors();
    }

    /**
     * Check if running on Windows
     */
    protected function isWindows(): bool
    {
        return DIRECTORY_SEPARATOR === '\\' || isset($_SERVER['TERM']) && $_SERVER['TERM'] === 'ANSI';
    }

    /**
     * Determine if colors should be used
     */
    protected function shouldUseColors(): bool
    {
        // Check if colors are disabled via environment
        if (getenv('NO_COLOR') !== false) {
            return false;
        }

        // Check if running in a non-interactive terminal
        if (!defined('STDOUT') || !function_exists('posix_isatty')) {
            return true;
        }

        // Check if output is piped or redirected
        if (php_sapi_name() === 'cli' && !posix_isatty(STDOUT)) {
            return false;
        }

        // On Windows, use colors only if ANSICON or ConEmuANSI is present
        if ($this->isWindows) {
            return getenv('ANSICON') !== false
                || getenv('ConEmuANSI') === 'ON'
                || getenv('TERM') === 'xterm';
        }

        return true;
    }

    /**
     * Enable or disable colors
     */
    public function setColors(bool $useColors): self
    {
        $this->useColors = $useColors;
        return $this;
    }

    /**
     * Check if colors are enabled
     */
    public function hasColors(): bool
    {
        return $this->useColors;
    }

    /**
     * Apply color to text
     */
    public function color(string $text, string $color): string
    {
        if (!$this->useColors) {
            return $text;
        }

        return $this->wrap($text, $color);
    }

    /**
     * Wrap text with ANSI codes
     */
    public function wrap(string $text, string $color): string
    {
        return "\033[{$color}m{$text}\033[0m";
    }

    /**
     * Format text as error (red)
     */
    public function error(string $text): string
    {
        return $this->color($text, self::RED_BOLD);
    }

    /**
     * Format text as success (green)
     */
    public function success(string $text): string
    {
        return $this->color($text, self::GREEN);
    }

    /**
     * Format text as warning (yellow)
     */
    public function warning(string $text): string
    {
        return $this->color($text, self::YELLOW);
    }

    /**
     * Format text as info (cyan)
     */
    public function info(string $text): string
    {
        return $this->color($text, self::CYAN);
    }

    /**
     * Format text as comment (magenta)
     */
    public function comment(string $text): string
    {
        return $this->color($text, self::MAGENTA);
    }

    /**
     * Format text as question (cyan + bold)
     */
    public function question(string $text): string
    {
        return $this->color($text, self::CYAN_BOLD);
    }

    /**
     * Format text as header (blue + bold)
     */
    public function header(string $text): string
    {
        return $this->color($text, self::BLUE_BOLD);
    }

    /**
     * Format table row
     */
    public function tableRow(array $columns, int $padding = 2): string
    {
        $maxLengths = [];
        foreach ($columns as $i => $col) {
            $maxLengths[$i] = strlen($col);
        }

        $result = '';
        foreach ($columns as $i => $col) {
            $result .= str_pad($col, $maxLengths[$i] + $padding);
        }

        return rtrim($result);
    }

    /**
     * Format a table with multiple rows
     */
    public function table(array $headers, array $rows): string
    {
        if (empty($headers) || empty($rows)) {
            return '';
        }

        // Calculate column widths
        $widths = [];
        foreach ($headers as $i => $header) {
            $widths[$i] = strlen($header);
        }

        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $widths[$i] = max($widths[$i], strlen($cell));
            }
        }

        // Format header
        $headerLine = '';
        foreach ($headers as $i => $header) {
            $headerLine .= str_pad($this->color($header, self::CYAN_BOLD), $widths[$i] + 2);
        }

        // Format separator
        $separator = '';
        foreach ($widths as $width) {
            $separator .= str_repeat('-', $width + 2);
        }

        // Format rows
        $lines = [];
        $lines[] = $headerLine;
        $lines[] = $separator;

        foreach ($rows as $row) {
            $line = '';
            foreach ($row as $i => $cell) {
                $line .= str_pad($cell, $widths[$i] + 2);
            }
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    /**
     * Format a progress bar
     */
    public function progressBar(int $current, int $total, int $width = 40): string
    {
        if ($total === 0) {
            $percent = 100;
        } else {
            $percent = (int)(($current / $total) * 100);
        }

        $filled = (int)(($current / $total) * $width);
        $empty = $width - $filled;

        $bar = str_repeat('=', $filled) . str_repeat('-', $empty);

        return "[{$bar}] {$percent}% ({$current}/{$total})";
    }

    /**
     * Clear line and move cursor to beginning
     */
    public function clearLine(): string
    {
        return "\r\033[K";
    }

    /**
     * Move cursor up n lines
     */
    public function cursorUp(int $lines = 1): string
    {
        return "\033[{$lines}A";
    }

    /**
     * Print a line with newline
     */
    public function line(string $text = ''): void
    {
        echo $text . PHP_EOL;
    }

    /**
     * Print without newline
     */
    public function write(string $text): void
    {
        echo $text;
    }

    /**
     * Print formatted text
     */
    public function print(string $text, string $style): void
    {
        echo $this->color($text, $style) . PHP_EOL;
    }
}
