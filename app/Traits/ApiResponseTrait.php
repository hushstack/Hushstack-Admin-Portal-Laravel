<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    protected function successResponse(
        $data = null,
        string $message = 'Success',
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'status_code' => $statusCode,
            'status' => 'ok',
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    protected function errorResponse(
        string $message = 'Error',
        int $statusCode = 400,
        $errors = null,
        ?string $errorCode = null
    ): JsonResponse {
        $response = [
            'status_code' => $statusCode,
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
        ];

        if ($errorCode) {
            $response['error_code'] = $errorCode;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Not Found Error (404)
     */
    protected function notFoundResponse(
        string $resource = 'Resource',
        ?int $id = null,
        ?string $errorCode = null
    ): JsonResponse {
        $message = $id
            ? "{$resource} with ID {$id} not found."
            : "{$resource} not found.";

        return $this->errorResponse($message, 404, null, $errorCode ?? 'NOT_FOUND');
    }

    /**
     * Validation Error (422)
     */
    protected function validationErrorResponse(
        $errors,
        string $message = 'Validation failed.',
        ?string $errorCode = null
    ): JsonResponse {
        return $this->errorResponse($message, 422, $errors, $errorCode ?? 'VALIDATION_ERROR');
    }

    /**
     * Forbidden Error (403)
     */
    protected function forbiddenResponse(
        string $message = 'Forbidden. You do not have permission to access this resource.',
        ?string $errorCode = null
    ): JsonResponse {
        return $this->errorResponse($message, 403, null, $errorCode ?? 'FORBIDDEN');
    }

    /**
     * Unauthorized Error (401)
     */
    protected function unauthorizedResponse(
        string $message = 'Unauthenticated. Please login first.',
        ?string $errorCode = null
    ): JsonResponse {
        return $this->errorResponse($message, 401, null, $errorCode ?? 'UNAUTHENTICATED');
    }

    /**
     * Server Error (500)
     */
    protected function serverErrorResponse(
        string $message = 'An unexpected error occurred. Please try again later.',
        ?string $errorCode = null
    ): JsonResponse {
        return $this->errorResponse($message, 500, null, $errorCode ?? 'SERVER_ERROR');
    }

    /**
     * Conflict Error (409) - Duplicate data
     */
    protected function conflictResponse(
        string $message = 'Resource already exists.',
        ?string $errorCode = null
    ): JsonResponse {
        return $this->errorResponse($message, 409, null, $errorCode ?? 'CONFLICT');
    }

    /**
     * Too Many Requests (429)
     */
    protected function tooManyRequestsResponse(
        string $message = 'Too many requests. Please slow down.',
        ?string $errorCode = null
    ): JsonResponse {
        return $this->errorResponse($message, 429, null, $errorCode ?? 'RATE_LIMIT');
    }
}
