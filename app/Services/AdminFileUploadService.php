<?php

namespace App\Services;

use App\Models\Admin\AdminFileRecord;
use App\Exceptions\FileUpload\FileUploadException;
use App\Exceptions\FileUpload\FileValidationException;
use App\Exceptions\FileUpload\FileSizeExceededException;
use App\Exceptions\FileUpload\InvalidFileTypeException;
use App\Exceptions\FileUpload\UploadFailedException;
use App\Exceptions\FileUpload\InvalidCredentialsException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Tos\TosClient;
use Tos\Model\CreateMultipartUploadInput;
use Tos\Model\UploadPartInput;
use Tos\Model\CompleteMultipartUploadInput;
use Tos\Model\UploadedPart;
use Tos\Model\AbortMultipartUploadInput;
use Tos\Exception\TosClientException;
use Tos\Exception\TosServerException;

class AdminFileUploadService
{
    // 分片大小: 5MB
    private const MULTIPART_CHUNK_SIZE = 5 * 1024 * 1024;

    // 大文件阈值: 超过此大小使用分片上传
    private const LARGE_FILE_THRESHOLD = 5 * 1024 * 1024;

    /**
     * 上传文件
     *
     * @param UploadedFile $file 上传的文件
     * @param array $options 上传选项
     * @return array 文件信息数组
     * @throws FileUploadException
     */
    public function upload(UploadedFile $file, array $options = []): array
    {
        $startTime = microtime(true);
        $disk = $options['disk'] ?? config('filesystems.default');

        Log::debug('Admin file upload started', [
            'original_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'disk' => $disk,
        ]);

        try {
            // 验证文件
            $this->validateFile($file);

            // 计算文件哈希
            $fileHash = $this->calculateFileHash($file);

            // 检查是否已存在相同文件（基于hash去重）
            $existingRecord = $this->findExistingFile($fileHash);

            if ($existingRecord) {
                Log::info('File already exists, reusing existing record', [
                    'file_id' => $existingRecord->file_id,
                    'file_hash' => $fileHash,
                ]);

                return $this->buildFileResponse($existingRecord, $file, true);
            }

            // 生成存储路径
            $storagePath = $this->generateStoragePath($file, $options['path'] ?? null);

            /*// 获取图片/视频/音频的额外信息
            $mediaInfo = $this->extractMediaInfo($file);*/

            // 根据文件大小选择上传方式
            if ($file->getSize() > self::LARGE_FILE_THRESHOLD && $disk === 'volcengine') {
                // 大文件使用分片上传
                $this->multipartUpload($file, $storagePath, $disk);
            } else {
                // 小文件使用普通上传
                $this->simpleUpload($file, $storagePath, $disk);
            }

            // 获取图片/视频/音频的额外信息
            $mediaInfo = $this->extractMediaInfo($file, $storagePath);

            // 创建文件记录
            $fileRecord = $this->createFileRecord($file, $storagePath, $fileHash, $mediaInfo);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('Admin file uploaded successfully', [
                'file_id' => $fileRecord->file_id,
                'original_name' => $file->getClientOriginalName(),
                'storage_path' => $storagePath,
                'file_size' => $file->getSize(),
                'duration_ms' => $duration,
            ]);

            return $this->buildFileResponse($fileRecord, $file, false);

        } catch (FileValidationException $e) {
            Log::warning('Admin file validation failed', [
                'original_name' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (TosClientException $e) {
            Log::error('Admin file upload failed due to network error', [
                'original_name' => $file->getClientOriginalName(),
                'error_message' => $e->getMessage(),
            ]);
            throw new UploadFailedException(
                $file->getClientOriginalName(),
                $disk,
                'Network error: ' . $e->getMessage(),
                $e
            );
        } catch (TosServerException $e) {
            $statusCode = $e->getStatusCode();
            if (in_array($statusCode, [401, 403])) {
                throw new InvalidCredentialsException(
                    $disk,
                    'Authentication failed (HTTP ' . $statusCode . '): ' . $e->getMessage(),
                    $e
                );
            }
            throw new UploadFailedException(
                $file->getClientOriginalName(),
                $disk,
                'Server error (HTTP ' . $statusCode . '): ' . $e->getMessage(),
                $e
            );
        } catch (FileUploadException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Admin file upload failed with unexpected error', [
                'original_name' => $file->getClientOriginalName(),
                'error_message' => $e->getMessage(),
            ]);
            throw new FileUploadException(
                'Unexpected error during file upload: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * 普通上传（小文件）
     */
    protected function simpleUpload(UploadedFile $file, string $storagePath, string $disk): void
    {
        $storageDisk = Storage::disk($disk);
        $uploaded = $storageDisk->put($storagePath, file_get_contents($file->getRealPath()));

        if (!$uploaded) {
            throw new UploadFailedException($file->getClientOriginalName(), $disk, 'Storage put operation returned false');
        }
    }

    /**
     * 分片上传（大文件）- TOS专用
     */
    protected function multipartUpload(UploadedFile $file, string $storagePath, string $disk): void
    {
        $config = config("filesystems.disks.{$disk}");
        $tosClient = $this->createTosClient($config);
        $bucket = $config['bucket'];
        $uploadId = null;

        try {
            // 1. 初始化分片上传
            $createInput = new CreateMultipartUploadInput($bucket, $storagePath);
            $createInput->setContentType($file->getMimeType());
            $createOutput = $tosClient->createMultipartUpload($createInput);
            $uploadId = $createOutput->getUploadID();

            Log::debug('Multipart upload initialized', [
                'upload_id' => $uploadId,
                'storage_path' => $storagePath,
            ]);

            // 2. 分片上传
            $uploadedParts = [];
            $fileHandle = fopen($file->getRealPath(), 'rb');
            $partNumber = 1;

            while (!feof($fileHandle)) {
                $chunk = fread($fileHandle, self::MULTIPART_CHUNK_SIZE);
                if ($chunk === false || strlen($chunk) === 0) {
                    break;
                }

                $uploadPartInput = new UploadPartInput($bucket, $storagePath, $uploadId, $partNumber);
                $uploadPartInput->setContent($chunk);
                $uploadPartInput->setContentLength(strlen($chunk));

                $uploadPartOutput = $tosClient->uploadPart($uploadPartInput);

                $uploadedParts[] = new UploadedPart($partNumber, $uploadPartOutput->getETag());

                Log::debug('Uploaded part', [
                    'part_number' => $partNumber,
                    'size' => strlen($chunk),
                    'etag' => $uploadPartOutput->getETag(),
                ]);

                $partNumber++;
            }

            fclose($fileHandle);

            // 3. 完成分片上传
            $completeInput = new CompleteMultipartUploadInput($bucket, $storagePath, $uploadId, $uploadedParts);
            $tosClient->completeMultipartUpload($completeInput);

            Log::info('Multipart upload completed', [
                'storage_path' => $storagePath,
                'total_parts' => count($uploadedParts),
            ]);

        } catch (\Exception $e) {
            // 取消分片上传
            if ($uploadId) {
                try {
                    $abortInput = new AbortMultipartUploadInput($bucket, $storagePath, $uploadId);
                    $tosClient->abortMultipartUpload($abortInput);
                    Log::info('Aborted multipart upload', ['upload_id' => $uploadId]);
                } catch (\Exception $abortException) {
                    Log::warning('Failed to abort multipart upload', [
                        'upload_id' => $uploadId,
                        'error' => $abortException->getMessage(),
                    ]);
                }
            }
            throw $e;
        }
    }

    /**
     * 创建TOS客户端
     */
    protected function createTosClient(array $config): TosClient
    {
        // 优先使用内网端点进行上传
        $endpoint = $config['internal_endpoint'] ?? $config['endpoint'];

        return new TosClient([
            'region' => $config['region'],
            'endpoint' => $endpoint,
            'ak' => $config['key'],
            'sk' => $config['secret'],
        ]);
    }

    /**
     * 查找已存在的文件（基于hash）
     */
    protected function findExistingFile(string $fileHash): ?AdminFileRecord
    {
        return AdminFileRecord::byHash($fileHash)->first();
    }

    /**
     * 验证文件
     */
    protected function validateFile(UploadedFile $file): void
    {
        if ($file->getSize() === 0) {
            throw new FileValidationException('File content cannot be empty');
        }

        $maxSize = config('filesystems.max_upload_size', 10240) * 1024;
        if ($file->getSize() > $maxSize) {
            throw new FileSizeExceededException($file->getSize(), $maxSize);
        }

        $allowedMimes = config('filesystems.allowed_mimes', []);
        $fileExtension = strtolower($file->getClientOriginalExtension());

        if (!empty($allowedMimes) && !in_array($fileExtension, $allowedMimes)) {
            throw new InvalidFileTypeException($fileExtension, $allowedMimes);
        }
    }

    /**
     * 计算文件哈希
     */
    protected function calculateFileHash(UploadedFile $file): string
    {
        return hash_file('sha256', $file->getRealPath());
    }


    /**
     * 生成存储路径
     * 格式: admin/image|video|audio|file/20260101/uuid.ext
     */
    protected function generateStoragePath(UploadedFile $file, ?string $customPath = null): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $uuid = Str::uuid()->toString();
        $filename = $uuid . '.' . $extension;

        if ($customPath) {
            $customPath = trim($customPath, '/');
            $customPath = preg_replace('/\.\.+/', '', $customPath);
            return $customPath . '/' . $filename;
        }

        $mediaType = $this->getMediaType($file->getMimeType());
        $dateFolder = Carbon::now()->format('Ymd');

        return sprintf('admin/%s/%s/%s', $mediaType, $dateFolder, $filename);
    }

    /**
     * 根据MIME类型获取媒体类型目录名
     */
    protected function getMediaType(string $mimeType): string
    {
        if (substr($mimeType, 0, 6) === 'image/') {
            return 'image';
        }
        if (substr($mimeType, 0, 6) === 'video/') {
            return 'video';
        }
        if (substr($mimeType, 0, 6) === 'audio/') {
            return 'audio';
        }
        return 'file';
    }

    /**
     * 提取媒体文件信息（图片尺寸、视频/音频时长等）
     */
    protected function extractMediaInfo(UploadedFile $file, $storagePath): array
    {
        $info = [
            'width' => 0,
            'height' => 0,
            'duration' => 0,
            'extra' => [],
        ];

        $mimeType = $file->getMimeType();

        // 图片尺寸
        if (substr($mimeType, 0, 6) === 'image/') {
            $imageInfo = @getimagesize($file->getRealPath());
            if ($imageInfo) {
                $info['width'] = $imageInfo[0];
                $info['height'] = $imageInfo[1];
            }
        }

        // 需要外部依赖 - 暂时不使用
        // 视频/音频时长（需要ffprobe，可选）
        /*if (substr($mimeType, 0, 6) === 'video/' || substr($mimeType, 0, 6) === 'audio/') {
            $duration = $this->getMediaDuration($file->getRealPath());
            if ($duration !== null) {
                $info['duration'] = $duration;
            }
        }*/

        // 使用 TOS 提供的媒体处理来解决这个问题
        // @doc https://www.volcengine.com/docs/6349/336156?lang=zh
        if (substr($mimeType, 0, 6) === 'video/' || substr($mimeType, 0, 6) === 'audio/') {
            list($width, $height, $duration) = $this->getMediaInfoWithTOS($storagePath);
            $info['width'] = $width;
            $info['height'] = $height;
            $info['duration'] = $duration;
        }

        return $info;
    }

    /**
     * 使用TOS获取媒体信息
     *
     * @param string $storagePath
     * @return int[]
     */
    protected function getMediaInfoWithTOS(string $storagePath): array
    {
        try {
            $mediaInfoURL = sprintf('%s?x-tos-process=video/info', $this->getBucketEndpoint($storagePath));
            $mediaInfoResponse = Http::get($mediaInfoURL);
            $mediaInfoResult = $mediaInfoResponse->json();

            $videoStream = collect($mediaInfoResult['streams'])->firstWhere('codec_type', 'video');

            $width = (int)($videoStream['width'] ?? 0);
            $height = (int)($videoStream['height'] ?? 0);
            $duration = (int)($mediaInfoResult['format']['duration'] ?? 0);

            return [$width, $height, $duration];
        } catch (\Exception $e) {
            Log::channel('daily')->error('使用TOS获取媒体信息失败: ' . $e->getMessage(), [
                'storage_path' => $storagePath
            ]);
            return [0, 0, 0];
        }
    }

    /**
     * 获取媒体文件时长（秒）
     */
    protected function getMediaDuration(string $filePath): int
    {
        // 检查ffprobe是否可用
        $ffprobe = trim(shell_exec('which ffprobe 2>/dev/null') ?? '');
        if (empty($ffprobe)) {
            return 0;
        }

        $command = sprintf(
            'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>/dev/null',
            escapeshellarg($filePath)
        );

        $output = shell_exec($command);
        if ($output !== null && is_numeric(trim($output))) {
            return (int)round((float)trim($output));
        }

        return 0;
    }

    /**
     * 创建文件记录
     */
    protected function createFileRecord(
        UploadedFile $file,
        string       $storagePath,
        string       $fileHash,
        array        $mediaInfo
    ): AdminFileRecord
    {
        return AdminFileRecord::create([
            'file_name' => $this->sanitizeFileName($file->getClientOriginalName()),
            'file_path' => $storagePath,
            'file_driver' => 'volcengine',
            'file_hash' => $fileHash,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'extension' => strtolower($file->getClientOriginalExtension()),
            'width' => $mediaInfo['width'] ?? 0,
            'height' => $mediaInfo['height'] ?? 0,
            'duration' => $mediaInfo['duration'] ?? 0,
            'extra' => !empty($mediaInfo['extra']) ? $mediaInfo['extra'] : new \stdClass(),
        ]);
    }

    /**
     * 清理文件名
     */
    protected function sanitizeFileName(string $originalName): string
    {
        $safeName = preg_replace('/[^\p{L}\p{N}\-_\s\.\(\)\[\]]/u', '_', $originalName);
        $safeName = preg_replace('/[_\s]+/', '_', $safeName);
        $safeName = preg_replace('/\.\.+/', '.', $safeName);
        $safeName = trim($safeName, '._-');

        if (empty($safeName)) {
            $safeName = 'file';
        }

        if (mb_strlen($safeName) > 100) {
            $safeName = mb_substr($safeName, 0, 100);
            $safeName = rtrim($safeName, '._-');
        }

        return $safeName;
    }

    /**
     * 构建文件响应数据
     */
    protected function buildFileResponse(AdminFileRecord $record, UploadedFile $file, bool $reused = false): array
    {
        return [
            'file_id' => $record->file_id,
            'url' => $this->generateFileUrl($record->file_path),
            'path' => $record->file_path,
            'original_name' => $file->getClientOriginalName(),
            'file_name' => $record->file_name,
            'file_size' => $record->file_size,
            'mime_type' => $record->mime_type,
            'extension' => $record->extension,
            'file_hash' => $record->file_hash,
            'width' => $record->width,
            'height' => $record->height,
            'duration' => $record->duration,
            'reused' => $reused,
            'created_at' => $record->created_at->toIso8601String(),
        ];
    }

    /**
     * 获取 bucket 域名
     *
     * @param string $storagePath
     * @return string
     */
    protected function getBucketEndpoint(string $storagePath): string
    {
        $config = config('filesystems.disks.volcengine');
        $schema = $config['schema'] ?? 'https';
        $cleanPath = ltrim($storagePath, '/');

        $endpoint = preg_replace('/^https?:\/\//', '', $config['endpoint'] ?? '');
        $bucket = $config['bucket'] ?? '';

        return $schema . '://' . $bucket . '.' . $endpoint . '/' . $cleanPath;
    }

    /**
     * 生成文件URL
     */
    public function generateFileUrl(string $storagePath): string
    {
        $config = config('filesystems.disks.volcengine');
        $schema = $config['schema'] ?? 'https';
        $cleanPath = ltrim($storagePath, '/');

        if (!empty($config['url'])) {
            return $schema . '://' . rtrim($config['url'], '/') . '/' . $cleanPath;
        }

        $endpoint = preg_replace('/^https?:\/\//', '', $config['endpoint'] ?? '');
        $bucket = $config['bucket'] ?? '';

        return $schema . '://' . $bucket . '.' . $endpoint . '/' . $cleanPath;
    }
}
