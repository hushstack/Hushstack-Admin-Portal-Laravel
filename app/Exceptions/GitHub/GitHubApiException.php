<?php

namespace App\Exceptions\GitHub;

use RuntimeException;

class GitHubApiException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 500,
        private readonly ?array $rateLimit = null,
    ) {
        parent::__construct($message, $statusCode);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function rateLimit(): ?array
    {
        return $this->rateLimit;
    }
}
