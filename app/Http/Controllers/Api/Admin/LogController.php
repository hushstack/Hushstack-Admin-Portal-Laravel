<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\LogEntryResource;
use App\Services\LogReaderService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Log Controller
 *
 * Admin endpoint to view Laravel log files.
 *
 * OWASP A01:2021 - Broken Access Control: Admin-only access
 * OWASP A09:2021 - Security Logging and Monitoring
 * Performance: Pagination with limits on file size
 * Clean Code: Delegates log reading to service layer
 */
class LogController extends Controller
{
    use ApiResponseTrait;

    /**
     * Constructor with dependency injection.
     */
    public function __construct(
        private readonly LogReaderService $logReader
    ) {}

    /**
     * Get log entries with filtering and pagination.
     *
     * Endpoint: GET /api/admin/logs
     *
     * Query Parameters:
     * - file: Log filename (default: laravel.log)
     * - level: Filter by level (DEBUG, INFO, ERROR, etc.)
     * - search: Search in message content
     * - date_from: Start date (YYYY-MM-DD)
     * - date_to: End date (YYYY-MM-DD)
     * - per_page: Items per page (default: 50, max: 100)
     * - page: Page number
     *
     * Security: Only admin users can access
     * Performance: Uses pagination and file size limits
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Validate and sanitize inputs
            $filename = $this->sanitizeFilename($request->input('file'));
            $level = $this->sanitizeLevel($request->input('level'));
            $search = $this->sanitizeSearch($request->input('search'));
            $dateFrom = $this->sanitizeDate($request->input('date_from'));
            $dateTo = $this->sanitizeDate($request->input('date_to'));
            $perPage = $this->sanitizePerPage($request->input('per_page', 50));
            $page = max(1, (int) $request->input('page', 1));

            // Get log entries
            $entries = $this->logReader->getLogEntries(
                filename: $filename,
                level: $level,
                search: $search,
                dateFrom: $dateFrom,
                dateTo: $dateTo,
                perPage: $perPage,
                page: $page
            );

            // Get available log files for filtering
            $logFiles = $this->logReader->getLogFiles();

            // Get available log levels
            $logLevels = $this->logReader->getLogLevels();

            return $this->successResponse([
                'entries' => LogEntryResource::collection($entries),
                'filters' => [
                    'files' => $logFiles,
                    'levels' => $logLevels,
                ],
                'pagination' => [
                    'current_page' => $entries->currentPage(),
                    'last_page' => $entries->lastPage(),
                    'per_page' => $entries->perPage(),
                    'total' => $entries->total(),
                ],
            ], 'Log entries retrieved successfully.');

        } catch (Throwable $e) {
            return $this->errorResponse(
                'Failed to read log files: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get available log files list.
     *
     * Endpoint: GET /api/admin/logs/files
     */
    public function files(): JsonResponse
    {
        try {
            $logFiles = $this->logReader->getLogFiles();

            return $this->successResponse([
                'files' => $logFiles,
            ], 'Log files retrieved successfully.');
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Failed to retrieve log files: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Sanitize filename to prevent path traversal.
     *
     * Security: OWASP A01:2021 - Path traversal prevention
     */
    private function sanitizeFilename(?string $filename): ?string
    {
        if ($filename === null) {
            return null;
        }

        // Remove any path components
        $filename = basename($filename);

        // Only allow alphanumeric, dashes, underscores, dots
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $filename)) {
            return null;
        }

        return $filename;
    }

    /**
     * Sanitize log level.
     */
    private function sanitizeLevel(?string $level): ?string
    {
        if ($level === null) {
            return null;
        }

        $allowed = ['DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'];
        $upper = strtoupper($level);

        return in_array($upper, $allowed, true) ? $upper : null;
    }

    /**
     * Sanitize search string.
     *
     * Security: Prevents injection attacks
     */
    private function sanitizeSearch(?string $search): ?string
    {
        if ($search === null) {
            return null;
        }

        // Remove null bytes and limit length
        $search = str_replace("\0", '', $search);
        $search = trim($search);

        return substr($search, 0, 200) ?: null;
    }

    /**
     * Sanitize date string.
     */
    private function sanitizeDate(?string $date): ?string
    {
        if ($date === null) {
            return null;
        }

        // Validate YYYY-MM-DD format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }

        return $date;
    }

    /**
     * Sanitize per page parameter.
     *
     * Performance: Prevents excessive data loading
     */
    private function sanitizePerPage(mixed $value): int
    {
        $perPage = (int) $value;

        // Limit to prevent memory exhaustion
        return max(1, min($perPage, 100));
    }
}
