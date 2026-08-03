<?php

namespace App\Exceptions\Messenger;

use RuntimeException;

class MessengerApiException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 503,
    ) {
        parent::__construct($message, $statusCode);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
