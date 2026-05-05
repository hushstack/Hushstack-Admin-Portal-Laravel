<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'status_code' => 422,
                    'status' => 'error',
                    'message' => 'Validation failed.',
                    'errors' => $e->errors(),
                    'error_code' => 'VALIDATION_ERROR',
                ], 422);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'status_code' => 401,
                    'status' => 'error',
                    'message' => 'Unauthenticated. Please login first.',
                    'errors' => null,
                    'error_code' => 'UNAUTHENTICATED',
                ], 401);
            }

            if ($e instanceof ModelNotFoundException) {
                return response()->json([
                    'status_code' => 404,
                    'status' => 'error',
                    'message' => 'Resource not found.',
                    'errors' => null,
                    'error_code' => 'NOT_FOUND',
                ], 404);
            }

            $statusCode = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
            $message = $statusCode >= 500 && ! config('app.debug')
                ? 'An unexpected error occurred. Please try again later.'
                : ($e->getMessage() ?: 'Request failed.');

            return response()->json([
                'status_code' => $statusCode,
                'status' => 'error',
                'message' => $message,
                'errors' => null,
                'error_code' => $statusCode >= 500 ? 'SERVER_ERROR' : 'REQUEST_ERROR',
                'exception' => config('app.debug') ? get_class($e) : null,
            ], $statusCode);
        });
    }
}
