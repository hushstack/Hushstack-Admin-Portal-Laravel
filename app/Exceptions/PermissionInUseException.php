<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

/**
 * Permission In Use Exception
 *
 * OOAD: Domain Exception - resource dependency violation
 */
class PermissionInUseException extends \Exception
{
    public function __construct(
        string $message = 'Permission is assigned to roles and cannot be deleted.',
        int $code = Response::HTTP_UNPROCESSABLE_ENTITY,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception with role count.
     */
    public static function withRoleCount(string $name, int $count): self
    {
        return new self(
            "Permission '{$name}' is assigned to {$count} role(s). Remove assignments before deleting."
        );
    }
}
