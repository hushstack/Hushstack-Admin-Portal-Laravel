<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

/**
 * Invalid Permission Exception
 *
 * OOAD: Domain Exception - invalid permission data
 */
class InvalidPermissionException extends \Exception
{
    public function __construct(
        string $message = 'Invalid permission data provided.',
        int $code = Response::HTTP_UNPROCESSABLE_ENTITY,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for missing permissions.
     *
     * @param array<int> $invalidIds
     */
    public static function idsNotFound(array $invalidIds): self
    {
        $ids = implode(', ', $invalidIds);
        return new self("One or more permissions do not exist: {$ids}");
    }
}
