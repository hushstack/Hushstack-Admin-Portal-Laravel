<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use SplFileInfo;

/**
 * Clear Logs Command
 *
 * Automatically clears Laravel log files based on retention policy.
 *
 * OWASP A09:2021 - Security Logging and Monitoring: Prevents log overflow
 * Performance: Frees disk space and improves log reading performance
 * Clean Code: Single responsibility - manages log lifecycle
 */
class ClearLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'logs:clear
                            {--days=7 : Retain logs for N days (default: 7)}
                            {--force : Skip confirmation prompt}
                            {--archive : Archive logs before clearing}
                            {--dry-run : Show what would be deleted without deleting}';

    /**
     * The console command description.
     */
    protected $description = 'Clear Laravel log files older than specified days';

    /**
     * Log storage path.
     */
    private string $logPath;

    /**
     * Archive storage path.
     */
    private string $archivePath;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->logPath = storage_path('logs');
        $this->archivePath = storage_path('logs/archive');

        $retentionDays = (int) $this->option('days');
        $isDryRun = $this->option('dry-run');
        $shouldArchive = $this->option('archive');

        // Security: Validate retention days (prevent accidental deletion of all logs)
        if ($retentionDays < 1) {
            $this->error('Retention days must be at least 1');
            return self::FAILURE;
        }

        $cutoffDate = Carbon::now()->subDays($retentionDays);

        $this->info("Log cleanup configuration:");
        $this->info("- Retention period: {$retentionDays} days");
        $this->info("- Cutoff date: " . $cutoffDate->format('d M Y'));
        $this->info("- Dry run: " . ($isDryRun ? 'Yes' : 'No'));
        $this->info("- Archive before delete: " . ($shouldArchive ? 'Yes' : 'No'));
        $this->newLine();

        // Get log files
        $logFiles = $this->getLogFiles();

        if (empty($logFiles)) {
            $this->warn('No log files found.');
            return self::SUCCESS;
        }

        // Categorize files
        $filesToDelete = [];
        $filesToKeep = [];

        foreach ($logFiles as $file) {
            /** @var SplFileInfo $file */
            $fileModified = Carbon::createFromTimestamp($file->getMTime());

            if ($fileModified->lt($cutoffDate)) {
                $filesToDelete[] = $file;
            } else {
                $filesToKeep[] = $file;
            }
        }

        // Display summary
        $this->info("Files to delete: " . count($filesToDelete));
        $this->info("Files to keep: " . count($filesToKeep));
        $this->newLine();

        if (empty($filesToDelete)) {
            $this->info('No old log files to clear.');
            return self::SUCCESS;
        }

        // List files to be deleted
        $this->info('Files to be deleted:');
        foreach ($filesToDelete as $file) {
            $modified = Carbon::createFromTimestamp($file->getMTime())->format('d M Y H:i');
            $size = $this->formatFileSize($file->getSize());
            $this->line("  - {$file->getFilename()} ({$modified}, {$size})");
        }
        $this->newLine();

        // Confirmation (unless --force or --dry-run)
        if (!$isDryRun && !$this->option('force')) {
            if (!$this->confirm('Do you want to proceed with deletion?')) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }
        }

        // Process deletions
        $deletedCount = 0;
        $archivedCount = 0;
        $failedCount = 0;
        $totalSizeFreed = 0;

        foreach ($filesToDelete as $file) {
            try {
                // Archive if requested
                if ($shouldArchive && !$isDryRun) {
                    if ($this->archiveFile($file)) {
                        $archivedCount++;
                    }
                }

                // Delete file
                if (!$isDryRun) {
                    $size = $file->getSize();
                    if (File::delete($file->getPathname())) {
                        $deletedCount++;
                        $totalSizeFreed += $size;
                        Log::info("Log file cleared", [
                            'file' => $file->getFilename(),
                            'size' => $size,
                            'retention_days' => $retentionDays,
                        ]);
                    } else {
                        $failedCount++;
                        Log::error("Failed to delete log file", [
                            'file' => $file->getFilename(),
                        ]);
                    }
                } else {
                    // Dry run - just count
                    $deletedCount++;
                    $totalSizeFreed += $file->getSize();
                }
            } catch (\Exception $e) {
                $failedCount++;
                Log::error("Exception clearing log file", [
                    'file' => $file->getFilename(),
                    'error' => $e->getMessage(),
                ]);
                $this->error("Error processing {$file->getFilename()}: {$e->getMessage()}");
            }
        }

        // Summary
        $this->newLine();
        if ($isDryRun) {
            $this->info("[DRY RUN] Would delete {$deletedCount} files (" . $this->formatFileSize($totalSizeFreed) . ")");
        } else {
            $this->info("Deleted {$deletedCount} files (" . $this->formatFileSize($totalSizeFreed) . ")");
            if ($archivedCount > 0) {
                $this->info("Archived {$archivedCount} files");
            }
            if ($failedCount > 0) {
                $this->warn("Failed to delete {$failedCount} files");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Get all log files.
     */
    private function getLogFiles(): array
    {
        if (!is_dir($this->logPath)) {
            return [];
        }

        $files = File::files($this->logPath);
        $logFiles = [];

        foreach ($files as $file) {
            /** @var SplFileInfo $file */
            if ($this->isValidLogFile($file)) {
                $logFiles[] = $file;
            }
        }

        return $logFiles;
    }

    /**
     * Archive a log file before deletion.
     */
    private function archiveFile(SplFileInfo $file): bool
    {
        try {
            if (!File::isDirectory($this->archivePath)) {
                File::makeDirectory($this->archivePath, 0755, true);
            }

            $archiveName = $file->getFilename() . '.' . Carbon::now()->format('Y-m-d-His') . '.bak';
            $archivePath = $this->archivePath . '/' . $archiveName;

            return File::copy($file->getPathname(), $archivePath);
        } catch (\Exception $e) {
            Log::error("Failed to archive log file", [
                'file' => $file->getFilename(),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Check if file is a valid log file.
     */
    private function isValidLogFile(SplFileInfo $file): bool
    {
        $extension = strtolower($file->getExtension());

        // Skip archive directory and non-log files
        if (str_contains($file->getPath(), 'archive')) {
            return false;
        }

        return in_array($extension, ['log'], true);
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
}
