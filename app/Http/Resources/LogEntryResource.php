<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Log Entry Resource
 *
 * Formats log entry data with standardized API response.
 *
 * Clean Code: Separates presentation from business logic
 * Security: Limits data exposure to necessary fields only
 */
class LogEntryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource === null) {
            return [];
        }

        $data = is_array($this->resource) ? $this->resource : (array) $this->resource;

        return [
            'date' => $data['date_formatted'] ?? null,           // "31 Jan 2026"
            'time' => $data['time_formatted'] ?? null,           // "07:58 AM"
            'environment' => $data['environment'] ?? 'unknown',       // "local"
            'level' => $data['level'] ?? 'UNKNOWN',                   // "ERROR"
            'level_class' => $this->getLevelClass($data['level'] ?? 'UNKNOWN'),
            'message' => $this->getTruncatedMessage($data['message'] ?? '', 500),
            'full_message' => $data['message'] ?? '',
            'has_stack_trace' => $this->hasStackTrace($data['message'] ?? ''),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'resource_type' => 'log_entry',
                'api_version' => 'v1',
            ],
        ];
    }

    /**
     * Customize the outgoing response for the resource.
     */
    public function withResponse(Request $request, \Illuminate\Http\JsonResponse $response): void
    {
        // Security headers
        $response->header('X-Content-Type-Options', 'nosniff');
        $response->header('X-Frame-Options', 'DENY');
    }

    /**
     * Get CSS class for log level.
     */
    private function getLevelClass(string $level): string
    {
        $classes = [
            'DEBUG' => 'debug',
            'INFO' => 'info',
            'NOTICE' => 'info',
            'WARNING' => 'warning',
            'ERROR' => 'error',
            'CRITICAL' => 'critical',
            'ALERT' => 'critical',
            'EMERGENCY' => 'critical',
        ];

        return $classes[$level] ?? 'default';
    }

    /**
     * Truncate message for preview.
     */
    private function getTruncatedMessage(string $message, int $length): string
    {
        if (strlen($message) <= $length) {
            return $message;
        }

        return substr($message, 0, $length) . '...';
    }

    /**
     * Check if message contains stack trace.
     */
    private function hasStackTrace(string $message): bool
    {
        return str_contains($message, 'Stack trace:') ||
               str_contains($message, '#0 ') ||
               str_contains($message, 'Trace:');
    }
}
