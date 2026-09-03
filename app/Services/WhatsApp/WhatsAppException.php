<?php

namespace App\Services\WhatsApp;

use RuntimeException;

/**
 * Thrown when the WhatsApp Cloud API rejects a request or returns an
 * unexpected shape. `$context` carries the decoded error body (Meta puts the
 * useful detail in error.message / error.error_data) for logging.
 */
class WhatsAppException extends RuntimeException
{
    public function __construct(string $message, public readonly array $context = [], int $code = 0)
    {
        parent::__construct($message, $code);
    }
}
