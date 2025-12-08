<?php

namespace App\Exceptions\FileUpload;

/**
 * Exception thrown when uploaded file size exceeds the allowed limit
 */
class FileSizeExceededException extends FileValidationException
{
    /**
     * Create a new file size exceeded exception instance
     *
     * @param int $fileSize The actual file size in bytes
     * @param int $maxSize The maximum allowed size in bytes
     * @param \Exception|null $previous
     */
    public function __construct(int $fileSize, int $maxSize, \Exception $previous = null)
    {
        $message = sprintf(
            'File size (%s) exceeds the maximum allowed size (%s)',
            $this->formatBytes($fileSize),
            $this->formatBytes($maxSize)
        );
        
        parent::__construct($message, 422, $previous);
    }

    /**
     * Format bytes to human readable format
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}