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
            'status'      => 'ok',
            'message'     => $message,
            'data'        => $data,
        ], $statusCode);
    }

    protected function errorResponse(
        string $message = 'Error',
        int $statusCode = 400,
               $errors = null
    ): JsonResponse {
        return response()->json([
            'status_code' => $statusCode,
            'status'      => 'error',
            'message'     => $message,
            'errors'      => $errors,
        ], $statusCode);
    }
}
