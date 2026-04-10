<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

/**
 * Protected Role Exception
 *
 * OOAD: Domain Exception - protected role modification attempt
 */
class ProtectedRoleException extends \Exception
{
    public function __construct(
        string $message = 'Cannot modify protected role.',
        int $code = Response::HTTP_FORBIDDEN,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for super admin role.
     */
    public static function superAdmin(): self
    {
        return new self('Cannot modify Super Admin role permissions.');
    }
}
