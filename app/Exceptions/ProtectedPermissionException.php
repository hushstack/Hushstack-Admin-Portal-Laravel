<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

/**
 * Protected Permission Exception
 *
 * OOAD: Domain Exception - system protection violation
 */
class ProtectedPermissionException extends \Exception
{
    public function __construct(
        string $message = 'Cannot modify protected system permission.',
        int $code = Response::HTTP_FORBIDDEN,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for modification attempt.
     */
    public static function forModification(string $slug): self
    {
        return new self("Cannot modify protected permission '{$slug}'.");
    }

    /**
     * Create exception for deletion attempt.
     */
    public static function forDeletion(string $slug): self
    {
        return new self("Cannot delete protected permission '{$slug}'.");
    }
}
