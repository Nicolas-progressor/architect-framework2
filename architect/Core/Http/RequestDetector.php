<?php

declare(strict_types=1);

namespace Architect\Core\Http;

/**
 * Detects if current request is an API request.
 */
class RequestDetector
{
    public function isApiRequest(): bool
    {
        // Check Accept header
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (str_contains($accept, 'application/json')) {
            return true;
        }

        // Check Content-Type header
        if (headers_sent($file, $line)) {
            $headers = headers_list();
            foreach ($headers as $header) {
                if (stripos($header, 'Content-Type:') === 0) {
                    if (stripos($header, 'application/json') !== false) {
                        return true;
                    }
                }
            }
        }

        // Check URL
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (str_contains($uri, '/api/') || str_ends_with($uri, '.json')) {
            return true;
        }

        // Check router notemplate (requires router instance)
        // This part is kept for compatibility but should be injected separately.
        // We'll rely on external detection.

        return false;
    }
}