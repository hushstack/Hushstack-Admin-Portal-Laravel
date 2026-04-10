<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

/**
 * Duplicate Permission Exception
 *
 * OOAD: Domain Exception - business rule violation
 */
class DuplicatePermissionException extends \Exception
{
    public function __construct(
        string $message = 'Permission slug already exists.',
        int $code = Response::HTTP_UNPROCESSABLE_ENTITY,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for slug.
     */
    public static function forSlug(string $slug): self
    {
        return new self("Permission slug '{$slug}' already exists.");
    }
}
