<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\File;
use SplFileInfo;

/**
 * Log Reader Service
 *
 * Reads and parses Laravel log files with security and performance considerations.
 *
 * OWASP A01:2021 - Broken Access Control: Only reads from configured log directory
 * OWASP A04:2021 - Insecure Design: Path traversal prevention
 * Performance: Lazy loading with pagination support
 * Clean Code: Single responsibility - log parsing only
 */
class LogReaderService
{
    /**
     * Log file path pattern
     */
    private string $logPath;

    /**
     * Maximum file size to read (10MB)
     */
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /**
     * Maximum lines per log file to process
     */
    private const MAX_LINES = 10000;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->logPath = storage_path('logs');
    }

    /**
     * Get available log files.
     *
     * Security: Validates file paths to prevent traversal
     * Performance: Uses scandir with filtering
     */
    public function getLogFiles(): array
    {
        if (!is_dir($this->logPath)) {
            return [];
        }

        $files = File::files($this->logPath);
        $logFiles = [];

        foreach ($files as $file) {
            /** @var SplFileInfo $file */
            if ($this->isValidLogFile($file)) {
                $logFiles[] = [
                    'name' => $file->getFilename(),
                    'size' => $this->formatFileSize($file->getSize()),
                    'modified' => $this->formatDate(Carbon::createFromTimestamp($file->getMTime())),
                ];
            }
        }

        // Sort by modification time desc
        usort($logFiles, fn ($a, $b) => strcmp($b['modified'], $a['modified']));

        return $logFiles;
    }

    /**
     * Read and parse log entries.
     *
     * Security: Validates filename to prevent path traversal
     * Performance: Limits file size and line count
     */
    public function getLogEntries(
        ?string $filename = null,
        ?string $level = null,
        ?string $search = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $perPage = 50,
        int $page = 1
    ): LengthAwarePaginator {
        $filename = $filename ?: 'laravel.log';

        // Security: Validate filename to prevent path traversal
        if (!$this->isValidFilename($filename)) {
            return new LengthAwarePaginator([], 0, $perPage, $page);
        }

        $filePath = $this->logPath . '/' . $filename;

        if (!File::exists($filePath) || !File::isReadable($filePath)) {
            return new LengthAwarePaginator([], 0, $perPage, $page);
        }

        // Security: Check file size limit
        $fileSize = File::size($filePath);
        if ($fileSize > self::MAX_FILE_SIZE) {
            // For large files, read last N lines only
            $entries = $this->readLastLines($filePath, self::MAX_LINES);
        } else {
            $entries = $this->parseLogFile($filePath);
        }

        // Apply filters
        $entries = $this->filterEntries($entries, $level, $search, $dateFrom, $dateTo);

        // Paginate results
        $total = count($entries);
        $offset = ($page - 1) * $perPage;
        $items = array_slice($entries, $offset, $perPage);

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()]
        );
    }

    /**
     * Parse log file into structured entries.
     *
     * Performance: Streams file for memory efficiency
     */
    private function parseLogFile(string $filePath): array
    {
        $entries = [];
        $handle = fopen($filePath, 'r');

        if (!$handle) {
            return $entries;
        }

        $currentEntry = null;
        $lineCount = 0;

        while (($line = fgets($handle)) !== false && $lineCount < self::MAX_LINES) {
            $lineCount++;
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            // Check if this is a new log entry start
            if ($this->isLogEntryStart($line)) {
                if ($currentEntry !== null) {
                    $entries[] = $currentEntry;
                }
                $currentEntry = $this->parseLogEntryStart($line);
            } elseif ($currentEntry !== null) {
                // Continue previous entry (stack trace, etc.)
                $currentEntry['message'] .= "\n" . $line;
                $currentEntry['full_log'] .= "\n" . $line;
            }
        }

        // Don't forget the last entry
        if ($currentEntry !== null) {
            $entries[] = $currentEntry;
        }

        fclose($handle);

        return $entries;
    }

    /**
     * Read last N lines from file efficiently.
     *
     * Performance: Memory-efficient for large files
     */
    private function readLastLines(string $filePath, int $lines): array
    {
        $entries = [];
        $buffer = [];
        $handle = fopen($filePath, 'r');

        if (!$handle) {
            return $entries;
        }

        // Read file in reverse to get last lines
        fseek($handle, 0, SEEK_END);
        $pos = ftell($handle);
        $lineCount = 0;
        $chunk = '';

        while ($pos > 0 && $lineCount < $lines * 2) {
            $pos--;
            fseek($handle, $pos);
            $char = fgetc($handle);

            if ($char === "\n" && !empty($chunk)) {
                $buffer[] = strrev($chunk);
                $chunk = '';
                $lineCount++;
            } else {
                $chunk .= $char;
            }
        }

        fclose($handle);

        if (!empty($chunk)) {
            $buffer[] = strrev($chunk);
        }

        // Reverse buffer to get chronological order
        $buffer = array_reverse($buffer);

        // Parse lines
        $currentEntry = null;
        foreach ($buffer as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            if ($this->isLogEntryStart($line)) {
                if ($currentEntry !== null) {
                    $entries[] = $currentEntry;
                }
                $currentEntry = $this->parseLogEntryStart($line);
            } elseif ($currentEntry !== null) {
                $currentEntry['message'] .= "\n" . $line;
                $currentEntry['full_log'] .= "\n" . $line;
            }
        }

        if ($currentEntry !== null) {
            $entries[] = $currentEntry;
        }

        return $entries;
    }

    /**
     * Check if line is a new log entry start.
     *
     * Pattern: [YYYY-MM-DD HH:MM:SS] LOG.LEVEL: Message
     */
    private function isLogEntryStart(string $line): bool
    {
        return preg_match('/^\[\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}\]/', $line) === 1;
    }

    /**
     * Parse log entry start line.
     */
    private function parseLogEntryStart(string $line): array
    {
        // Pattern: [2026-04-03 07:58:18] local.ERROR: message {context}
        $pattern = '/^\[(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2})\]\s+(\w+)\.(\w+):\s*(.+)$/';

        if (preg_match($pattern, $line, $matches)) {
            $timestamp = Carbon::parse($matches[1]);

            return [
                'timestamp_raw' => $matches[1],
                'date_formatted' => $this->formatDate($timestamp),
                'time_formatted' => $this->formatTime($timestamp),
                'environment' => $matches[2],
                'level' => strtoupper($matches[3]),
                'message' => $matches[4],
                'full_log' => $line,
            ];
        }

        // Fallback for unexpected format
        return [
            'timestamp_raw' => null,
            'date_formatted' => null,
            'time_formatted' => null,
            'environment' => 'unknown',
            'level' => 'UNKNOWN',
            'message' => $line,
            'full_log' => $line,
        ];
    }

    /**
     * Filter log entries based on criteria.
     */
    private function filterEntries(
        array $entries,
        ?string $level,
        ?string $search,
        ?string $dateFrom,
        ?string $dateTo
    ): array {
        return array_filter($entries, function ($entry) use ($level, $search, $dateFrom, $dateTo): bool {
            // Level filter
            if ($level !== null && strtoupper($entry['level']) !== strtoupper($level)) {
                return false;
            }

            // Search filter (case-insensitive)
            if ($search !== null && stripos($entry['message'], $search) === false) {
                return false;
            }

            // Date range filters
            if ($dateFrom !== null && $entry['timestamp_raw'] !== null) {
                if ($entry['timestamp_raw'] < $dateFrom) {
                    return false;
                }
            }

            if ($dateTo !== null && $entry['timestamp_raw'] !== null) {
                if ($entry['timestamp_raw'] > $dateTo) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Validate filename to prevent path traversal.
     *
     * Security: OWASP A01:2021 - Path traversal prevention
     */
    private function isValidFilename(string $filename): bool
    {
        // Allow only alphanumeric, dashes, underscores, dots
        return preg_match('/^[a-zA-Z0-9._-]+$/', $filename) === 1;
    }

    /**
     * Check if file is a valid log file.
     */
    private function isValidLogFile(SplFileInfo $file): bool
    {
        $extension = strtolower($file->getExtension());
        return in_array($extension, ['log'], true);
    }

    /**
     * Format date as "31 Jan 2026"
     */
    private function formatDate(Carbon $date): string
    {
        return $date->format('d M Y');
    }

    /**
     * Format time as "00:00 AM/PM"
     */
    private function formatTime(Carbon $date): string
    {
        return $date->format('h:i A');
    }

    /**
     * Format file size for display.
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Get available log levels.
     */
    public function getLogLevels(): array
    {
        return ['DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'];
    }
}
