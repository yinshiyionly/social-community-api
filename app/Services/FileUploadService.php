<?php

namespace App\Services;

use App\Models\FileRecord;
use App\Exceptions\FileUpload\FileUploadException;
use App\Exceptions\FileUpload\FileValidationException;
use App\Exceptions\FileUpload\FileSizeExceededException;
use App\Exceptions\FileUpload\InvalidFileTypeException;
use App\Exceptions\FileUpload\InvalidMimeTypeException;
use App\Exceptions\FileUpload\UploadFailedException;
use App\Exceptions\FileUpload\InvalidCredentialsException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Tos\Exception\TosClientException;
use Tos\Exception\TosServerException;


class FileUploadService
{
    /**
     * Upload a file to the configured storage disk
     *
     * @param UploadedFile $file The uploaded file
     * @param array $options Upload options
     * @return array File information array
     * @throws FileUploadException
     */
    public function upload(UploadedFile $file, array $options = []): array
    {
        $startTime = microtime(true);
        $disk = $options['disk'] ?? config('filesystems.default');
        $storagePath = null;
        $fileUploaded = false;

        // Record upload start time and debug log
        Log::debug('File upload started', [
            'original_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'user_id' => $options['user_id'] ?? null,
            'disk' => $disk,
            'start_time' => Carbon::now()->toIso8601String()
        ]);

        try {
            // Validate file
            $this->validateFile($file);

            // Generate storage path
            $storagePath = $this->generateStoragePath($file, $options['path'] ?? null);

            // Calculate file hash
            $fileHash = $this->calculateFileHash($file);

            // Get storage disk
            $storageDisk = Storage::disk($disk);

            // Upload file through Storage::disk()->put()
            $uploaded = $storageDisk->put($storagePath, file_get_contents($file->getRealPath()));

            if (!$uploaded) {
                throw new UploadFailedException($file->getClientOriginalName(), $disk, 'Storage put operation returned false');
            }

            $fileUploaded = true;

            // Create file record in database
            $fileRecord = $this->createFileRecord($file, $storagePath, $fileHash, $disk, $options);

            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2); // Duration in milliseconds

            // Record upload end time and success log
            Log::info('File uploaded successfully', [
                'file_id' => $fileRecord->id,
                'original_name' => $file->getClientOriginalName(),
                'storage_path' => $storagePath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'user_id' => $options['user_id'] ?? null,
                'duration_ms' => $duration,
                'end_time' => Carbon::now()->toIso8601String()
            ]);

            // Return file information array (id, url, path, etc.)
            return [
                'id' => $fileRecord->id,
                'url' => $this->generateFileUrl($storagePath, $disk),
                'path' => $storagePath,
                'original_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'file_hash' => $fileHash,
                'storage_disk' => $disk,
                'uploaded_at' => $fileRecord->created_at->toIso8601String()
            ];

        } catch (FileValidationException $e) {
            // Re-throw validation exceptions without cleanup
            Log::warning('File validation failed', [
                'original_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'error' => $e->getMessage(),
                'user_id' => $options['user_id'] ?? null
            ]);
            throw $e;
        } catch (TosClientException $e) {
            // Handle network errors (connection issues, timeouts)
            $this->cleanupUploadedFile($disk, $storagePath, $fileUploaded);

            Log::error('File upload failed due to network error', [
                'original_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'error_type' => 'network_error',
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'user_id' => $options['user_id'] ?? null,
                'storage_disk' => $disk,
                'storage_path' => $storagePath
            ]);

            throw new UploadFailedException(
                $file->getClientOriginalName(),
                $disk,
                'Network error: ' . $e->getMessage(),
                $e
            );
        } catch (TosServerException $e) {
            // Handle authentication and server errors
            $this->cleanupUploadedFile($disk, $storagePath, $fileUploaded);

            $statusCode = $e->getStatusCode();

            if (in_array($statusCode, [401, 403])) {
                // Authentication/authorization errors
                Log::error('File upload failed due to authentication error', [
                    'original_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'error_type' => 'authentication_error',
                    'error_message' => $e->getMessage(),
                    'status_code' => $statusCode,
                    'request_id' => $e->getRequestId(),
                    'user_id' => $options['user_id'] ?? null,
                    'storage_disk' => $disk,
                    'storage_path' => $storagePath
                ]);

                throw new InvalidCredentialsException(
                    $disk,
                    'Authentication failed (HTTP ' . $statusCode . '): ' . $e->getMessage(),
                    $e
                );
            } else {
                // Other server errors
                Log::error('File upload failed due to server error', [
                    'original_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'error_type' => 'server_error',
                    'error_message' => $e->getMessage(),
                    'status_code' => $statusCode,
                    'request_id' => $e->getRequestId(),
                    'user_id' => $options['user_id'] ?? null,
                    'storage_disk' => $disk,
                    'storage_path' => $storagePath
                ]);

                throw new UploadFailedException(
                    $file->getClientOriginalName(),
                    $disk,
                    'Server error (HTTP ' . $statusCode . '): ' . $e->getMessage(),
                    $e
                );
            }
        } catch (FileUploadException $e) {
            // Re-throw existing file upload exceptions
            $this->cleanupUploadedFile($disk, $storagePath, $fileUploaded);

            Log::error('File upload failed', [
                'original_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'error_type' => 'file_upload_error',
                'error_message' => $e->getMessage(),
                'user_id' => $options['user_id'] ?? null,
                'storage_disk' => $disk,
                'storage_path' => $storagePath
            ]);
            throw $e;
        } catch (\Exception $e) {
            // Catch and convert other unexpected exceptions
            $this->cleanupUploadedFile($disk, $storagePath, $fileUploaded);

            Log::error('File upload failed with unexpected error', [
                'original_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'error_type' => 'unexpected_error',
                'error_message' => $e->getMessage(),
                'user_id' => $options['user_id'] ?? null,
                'storage_disk' => $disk,
                'storage_path' => $storagePath
            ]);

            throw new FileUploadException(
                'Unexpected error during file upload: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * 根据文件扩展名获取MIME类型
     *
     * @param string $extension 文件扩展名
     * @return string MIME类型
     */
    public function getMimeTypeByExtension(string $extension): string
    {
        $mimeMap = [
            // 图片
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'bmp' => 'image/bmp',
            // 文档
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            // 文本
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            // 压缩包
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
        ];

        $extension = strtolower($extension);
        return $mimeMap[$extension] ?? 'application/octet-stream';
    }

    /**
     * Validate the uploaded file
     *
     * @param UploadedFile $file
     * @throws FileValidationException
     */
    protected function validateFile(UploadedFile $file): void
    {
        // Check if file content is not empty
        if ($file->getSize() === 0) {
            throw new FileValidationException('File content cannot be empty');
        }

        // Check file size limit
        $maxSize = config('filesystems.max_upload_size', 10240) * 1024; // Convert KB to bytes
        if ($file->getSize() > $maxSize) {
            throw new FileSizeExceededException($file->getSize(), $maxSize);
        }

        // Check file type
        $allowedMimes = config('filesystems.allowed_mimes', []);
        $fileExtension = strtolower($file->getClientOriginalExtension());

        if (!empty($allowedMimes) && !in_array($fileExtension, $allowedMimes)) {
            throw new InvalidFileTypeException($fileExtension, $allowedMimes);
        }

        // Validate MIME type matches extension
        $fileMimeType = $file->getMimeType();
        $expectedMimes = $this->getExpectedMimeTypes($fileExtension);

        /*if (!empty($expectedMimes) && !in_array($fileMimeType, $expectedMimes)) {
            $expectedMimeType = implode(', ', $expectedMimes);
            throw new InvalidMimeTypeException($fileMimeType, $expectedMimeType, $fileExtension);
        }*/
    }

    /**
     * Generate a safe storage path for the file
     *
     * @param UploadedFile $file
     * @param string|null $customPath
     * @return string
     */
    protected function generateStoragePath(UploadedFile $file, ?string $customPath = null): string
    {
        $now = Carbon::now();
        $year = $now->format('Y');
        $month = $now->format('m');
        $day = $now->format('d');

        // Generate pure UUID filename for storage
        $extension = $file->getClientOriginalExtension();
        $uuid = Str::uuid()->toString();
        $filename = $uuid . '.' . $extension;

        // Build path
        if ($customPath) {
            // Sanitize custom path
            $customPath = trim($customPath, '/');
            $customPath = preg_replace('/\.\.+/', '', $customPath); // Remove path traversal
            return $customPath . '/' . $filename;
        }

        return "uploads/{$year}/{$month}/{$day}/{$filename}";
    }

    /**
     * Sanitize original filename for database storage
     *
     * @param string $originalName
     * @return string
     */
    protected function sanitizeOriginalName(string $originalName): string
    {
        // Remove dangerous characters but keep meaningful ones
        // Allow: letters, numbers, Chinese characters, hyphens, underscores, spaces, dots, parentheses, brackets
        $safeName = preg_replace('/[^\p{L}\p{N}\-_\s\.\(\)\[\]]/u', '_', $originalName);

        // Replace multiple consecutive underscores/spaces with single underscore
        $safeName = preg_replace('/[_\s]+/', '_', $safeName);

        // Remove path traversal patterns
        $safeName = preg_replace('/\.\.+/', '.', $safeName);

        // Trim unwanted characters from start and end
        $safeName = trim($safeName, '._-');

        if (empty($safeName)) {
            $safeName = 'file';
        }

        // Limit to 50 characters for better identification
        if (mb_strlen($safeName) > 50) {
            $safeName = mb_substr($safeName, 0, 50);
            $safeName = rtrim($safeName, '._-');
        }

        return $safeName;
    }

    /**
     * Calculate file hash for deduplication
     *
     * @param UploadedFile $file
     * @return string
     */
    protected function calculateFileHash(UploadedFile $file): string
    {
        return hash_file('sha256', $file->getRealPath());
    }

    /**
     * Create file record in database
     *
     * @param UploadedFile $file
     * @param string $storagePath
     * @param string $fileHash
     * @param string $disk
     * @param array $options
     * @return FileRecord
     */
    protected function createFileRecord(
        UploadedFile $file,
        string $storagePath,
        string $fileHash,
        string $disk,
        array $options
    ): FileRecord {
        return FileRecord::create([
            'original_name' => $this->sanitizeOriginalName($file->getClientOriginalName()),
            'storage_path' => $storagePath,
            'storage_disk' => $disk,
            'file_hash' => $fileHash,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'extension' => $file->getClientOriginalExtension(),
            'user_id' => $options['user_id'] ?? null,
        ]);
    }

    /**
     * Get expected MIME types for a file extension
     *
     * @param string $extension
     * @return array
     */
    protected function getExpectedMimeTypes(string $extension): array
    {
        $mimeMap = [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
            'svg' => ['image/svg+xml'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'ppt' => ['application/vnd.ms-powerpoint'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            'txt' => ['text/plain'],
            'csv' => ['text/csv', 'application/csv'],
            'zip' => ['application/zip'],
            'rar' => ['application/x-rar-compressed'],
            'mp4' => ['video/mp4'],
            'avi' => ['video/x-msvideo'],
            'mov' => ['video/quicktime'],
            'mp3' => ['audio/mpeg'],
            'wav' => ['audio/wav', 'audio/x-wav'],
        ];

        return $mimeMap[$extension] ?? [];
    }

    /**
     * Clean up uploaded file if upload fails after file was stored
     *
     * @param string $disk Storage disk name
     * @param string|null $storagePath Path to the uploaded file
     * @param bool $fileUploaded Whether the file was successfully uploaded
     */
    protected function cleanupUploadedFile(string $disk, ?string $storagePath, bool $fileUploaded): void
    {
        if (!$fileUploaded || !$storagePath) {
            return;
        }

        try {
            $storageDisk = Storage::disk($disk);

            if ($storageDisk->exists($storagePath)) {
                $storageDisk->delete($storagePath);

                Log::info('Cleaned up uploaded file after failure', [
                    'storage_disk' => $disk,
                    'storage_path' => $storagePath
                ]);
            }
        } catch (\Exception $e) {
            // Log cleanup failure but don't throw exception
            Log::warning('Failed to cleanup uploaded file after upload failure', [
                'storage_disk' => $disk,
                'storage_path' => $storagePath,
                'cleanup_error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Generate file URL based on storage path and disk
     *
     * @param string $storagePath
     * @param string $disk
     * @return string
     */
    public function generateFileUrl(string $storagePath, string $disk = null): string
    {
        $disk = $disk ?? config('filesystems.default');

        // For non-volcengine disks, use Laravel's default URL generation
        if ($disk !== 'volcengine') {
            return Storage::disk($disk)->url($storagePath);
        }

        // For volcengine disk, use custom URL generation logic
        $config = config("filesystems.disks.{$disk}");
        $schema = $config['schema'] ?? 'https';

        // Clean storage path
        $cleanPath = ltrim($storagePath, '/');

        // If CDN URL is configured (without protocol)
        if (!empty($config['url'])) {
            return $schema . '://' . rtrim($config['url'], '/') . '/' . $cleanPath;
        }

        // Otherwise, construct bucket.endpoint URL
        $endpoint = $config['endpoint'] ?? '';
        $bucket = $config['bucket'] ?? '';

        // Remove protocol from endpoint if present
        $endpointWithoutProtocol = preg_replace('/^https?:\/\//', '', $endpoint);

        return $schema . '://' . $bucket . '.' . $endpointWithoutProtocol . '/' . $cleanPath;
    }

    /**
     * Extract path from a complete URL (removes domain prefix)
     *
     * @param string $url Complete URL to extract path from
     * @return string Path without domain prefix
     */
    public function extractPathFromUrl(string $url): string
    {
        // Parse the URL to extract the path
        $parsedUrl = parse_url($url);

        if (!isset($parsedUrl['path'])) {
            // If no path found, return original URL
            return $url;
        }

        // Extract and clean the path
        return ltrim($parsedUrl['path'], '/');
    }
}
