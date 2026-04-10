<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

/**
 * Permission Not Found Exception
 *
 * OOAD: Domain Exception - specific to permission domain
 */
class PermissionNotFoundException extends \Exception
{
    public function __construct(
        string $message = 'Permission not found.',
        int $code = Response::HTTP_NOT_FOUND,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for ID.
     */
    public static function forId(int $id): self
    {
        return new self("Permission with ID {$id} not found.");
    }

    /**
     * Create exception for slug.
     */
    public static function forSlug(string $slug): self
    {
        return new self("Permission with slug '{$slug}' not found.");
    }
}
