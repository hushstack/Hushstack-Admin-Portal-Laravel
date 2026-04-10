<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

/**
 * Permission Not Assigned Exception
 *
 * OOAD: Domain Exception - assignment state violation
 */
class PermissionNotAssignedException extends \Exception
{
    public function __construct(
        string $message = 'Permission is not assigned to this role.',
        int $code = Response::HTTP_UNPROCESSABLE_ENTITY,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception with specific permission and role.
     */
    public static function forPermissionAndRole(string $permissionName, string $roleName): self
    {
        return new self("Permission '{$permissionName}' is not assigned to role '{$roleName}'.");
    }
}
