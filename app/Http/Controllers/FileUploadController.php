<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileUploadRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\FileRecordResource;
use App\Models\FileRecord;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileUploadController extends Controller
{
    protected FileUploadService $uploadService;

    public function __construct()
    {
        $this->uploadService = new FileUploadService();
    }

    /**
     * Upload a single file
     * POST /api/files/upload
     *
     * @param FileUploadRequest $request
     * @return JsonResponse
     */
    public function upload(FileUploadRequest $request): JsonResponse
    {
        try {
            $file = $request->file('file');
            $options = [
                'disk' => $request->input('disk', config('filesystems.default')),
                'path' => $request->input('path'),
                'user_id' => auth()->id(),
            ];

            $result = $this->uploadService->upload($file, $options);

            return ApiResponse::success(['data' => $result], '文件上传成功');
        } catch (\Exception $e) {
            Log::error('File upload controller error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'file_name' => $request->file('file') ?? ''
            ]);

            return ApiResponse::error('文件上传失败：' . $e->getMessage(), 500);
        }
    }

    /**
     * Upload multiple files
     * POST /api/files/upload-multiple
     *
     * @param FileUploadRequest $request
     * @return JsonResponse
     */
    public function uploadMultiple(FileUploadRequest $request): JsonResponse
    {
        try {
            $files = $request->file('files');

            if (!$files || !is_array($files)) {
                return ApiResponse::error('请选择要上传的文件', 422);
            }

            $results = [];
            $errors = [];
            $successCount = 0;

            foreach ($files as $index => $file) {
                try {
                    $options = [
                        'disk' => $request->input('disk', config('filesystems.default')),
                        'path' => $request->input('path'),
                        'user_id' => auth()->id(),
                    ];

                    $result = $this->uploadService->upload($file, $options);
                    $results[] = $result;
                    $successCount++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'file_index' => $index,
                        'file_name' => $file->getClientOriginalName(),
                        'error' => $e->getMessage()
                    ];

                    Log::warning('Single file failed in batch upload', [
                        'file_index' => $index,
                        'file_name' => $file->getClientOriginalName(),
                        'error' => $e->getMessage(),
                        'user_id' => auth()->id()
                    ]);
                }
            }

            $totalFiles = count($files);
            $message = "批量上传完成：成功 {$successCount}/{$totalFiles} 个文件";

            if (!empty($errors)) {
                $message .= "，" . count($errors) . " 个文件上传失败";
            }

            return ApiResponse::success([
                'results' => $results,
                'errors' => $errors,
                'summary' => [
                    'total' => $totalFiles,
                    'success' => $successCount,
                    'failed' => count($errors)
                ]
            ], $message);

        } catch (\Exception $e) {
            Log::error('Batch file upload controller error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return ApiResponse::error('批量文件上传失败：' . $e->getMessage(), 500);
        }
    }

    /**
     * Get file information
     * GET /api/files/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $file = FileRecord::with('user')->findOrFail($id);

            return ApiResponse::resource($file, FileRecordResource::class, '文件信息获取成功');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::notFound('文件不存在');
        } catch (\Exception $e) {
            Log::error('File show controller error', [
                'file_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return ApiResponse::error('获取文件信息失败：' . $e->getMessage(), 500);
        }
    }

    /**
     * Get file list with filtering and pagination
     * GET /api/files
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = FileRecord::with('user');

            // Filter by user_id
            if ($userId = $request->input('user_id')) {
                $query->byUser($userId);
            }

            // Filter by storage disk
            if ($disk = $request->input('disk')) {
                $query->byDisk($disk);
            }

            // Filter by file extension
            if ($extension = $request->input('extension')) {
                $query->where('extension', $extension);
            }

            // Filter by MIME type
            if ($mimeType = $request->input('mime_type')) {
                $query->where('mime_type', 'like', $mimeType . '%');
            }

            // Search by original filename
            if ($search = $request->input('search')) {
                $query->where('original_name', 'like', '%' . $search . '%');
            }

            // Date range filter
            if ($startDate = $request->input('start_date')) {
                $query->where('created_at', '>=', $startDate);
            }
            if ($endDate = $request->input('end_date')) {
                $query->where('created_at', '<=', $endDate);
            }

            // Order by creation time (latest first)
            $query->latest();

            // Pagination
            $perPage = min($request->input('per_page', 20), 100); // Max 100 items per page
            $files = $query->paginate($perPage);

            return ApiResponse::paginate($files, FileRecordResource::class, '文件列表获取成功');

        } catch (\Exception $e) {
            Log::error('File index controller error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_params' => $request->all()
            ]);

            return ApiResponse::error('获取文件列表失败：' . $e->getMessage(), 500);
        }
    }

    /**
     * Soft delete a file
     * DELETE /api/files/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $file = FileRecord::findOrFail($id);

            // Optional: Check if user has permission to delete this file
            // if (auth()->id() !== $file->user_id && !auth()->user()->hasRole('admin')) {
            //     return ApiResponse::forbidden('无权限删除此文件');
            // }

            $file->delete();

            Log::info('File soft deleted', [
                'file_id' => $id,
                'original_name' => $file->original_name,
                'user_id' => auth()->id(),
                'deleted_by' => auth()->id()
            ]);

            return ApiResponse::deleted('文件已删除');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::notFound('文件不存在');
        } catch (\Exception $e) {
            Log::error('File delete controller error', [
                'file_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return ApiResponse::error('删除文件失败：' . $e->getMessage(), 500);
        }
    }

    /**
     * Download a file
     * GET /api/files/{id}/download
     *
     * @param int $id
     * @return JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download(int $id)
    {
        try {
            $file = FileRecord::findOrFail($id);

            // Optional: Check if user has permission to download this file
            // if (auth()->id() !== $file->user_id && !auth()->user()->hasRole('admin')) {
            //     return ApiResponse::forbidden('无权限下载此文件');
            // }

            // Check if file exists in storage
            if (!$file->exists()) {
                return ApiResponse::notFound('文件不存在或已被删除');
            }

            Log::info('File downloaded', [
                'file_id' => $id,
                'original_name' => $file->original_name,
                'user_id' => auth()->id(),
                'downloaded_by' => auth()->id()
            ]);

            // Return file download response
            return \Illuminate\Support\Facades\Storage::disk($file->storage_disk)
                ->download($file->storage_path, $file->original_name);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::notFound('文件不存在');
        } catch (\Exception $e) {
            Log::error('File download controller error', [
                'file_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return ApiResponse::error('文件下载失败：' . $e->getMessage(), 500);
        }
    }

    /**
     * Get file statistics
     * GET /api/files/statistics/overview
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $query = FileRecord::query();

            // Filter by user if specified
            if ($userId = $request->input('user_id')) {
                $query->byUser($userId);
            }

            // Filter by storage disk if specified
            if ($disk = $request->input('disk')) {
                $query->byDisk($disk);
            }

            // Date range filter
            if ($startDate = $request->input('start_date')) {
                $query->where('created_at', '>=', $startDate);
            }
            if ($endDate = $request->input('end_date')) {
                $query->where('created_at', '<=', $endDate);
            }

            $statistics = [
                'total_files' => $query->count(),
                'total_size' => $query->sum('file_size'),
                'total_size_formatted' => $this->formatBytes($query->sum('file_size')),
                'by_extension' => $query->selectRaw('extension, COUNT(*) as count, SUM(file_size) as total_size')
                    ->groupBy('extension')
                    ->orderByDesc('count')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'extension' => $item->extension,
                            'count' => $item->count,
                            'total_size' => $item->total_size,
                            'total_size_formatted' => $this->formatBytes($item->total_size)
                        ];
                    }),
                'by_disk' => $query->selectRaw('storage_disk, COUNT(*) as count, SUM(file_size) as total_size')
                    ->groupBy('storage_disk')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'disk' => $item->storage_disk,
                            'count' => $item->count,
                            'total_size' => $item->total_size,
                            'total_size_formatted' => $this->formatBytes($item->total_size)
                        ];
                    }),
                'recent_uploads' => $query->latest()
                    ->limit(10)
                    ->get(['id', 'original_name', 'file_size', 'created_at'])
                    ->map(function ($file) {
                        return [
                            'id' => $file->id,
                            'name' => $file->original_name,
                            'size' => $file->file_size,
                            'size_formatted' => $this->formatBytes($file->file_size),
                            'uploaded_at' => $file->created_at->toIso8601String()
                        ];
                    })
            ];

            return ApiResponse::success($statistics, '文件统计信息获取成功');

        } catch (\Exception $e) {
            Log::error('File statistics controller error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_params' => $request->all()
            ]);

            return ApiResponse::error('获取文件统计信息失败：' . $e->getMessage(), 500);
        }
    }

    /**
     * Batch delete files
     * DELETE /api/files/batch
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function batchDestroy(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|exists:file_records,id'
            ]);

            $ids = $request->input('ids');
            $files = FileRecord::whereIn('id', $ids)->get();

            if ($files->isEmpty()) {
                return ApiResponse::notFound('未找到要删除的文件');
            }

            // Optional: Check permissions for each file
            // foreach ($files as $file) {
            //     if (auth()->id() !== $file->user_id && !auth()->user()->hasRole('admin')) {
            //         return ApiResponse::forbidden('无权限删除部分文件');
            //     }
            // }

            $deletedCount = 0;
            $errors = [];

            foreach ($files as $file) {
                try {
                    $file->delete();
                    $deletedCount++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'file_id' => $file->id,
                        'file_name' => $file->original_name,
                        'error' => $e->getMessage()
                    ];
                }
            }

            Log::info('Batch file deletion completed', [
                'total_requested' => count($ids),
                'deleted_count' => $deletedCount,
                'error_count' => count($errors),
                'user_id' => auth()->id()
            ]);

            $message = "批量删除完成：成功删除 {$deletedCount} 个文件";
            if (!empty($errors)) {
                $message .= "，" . count($errors) . " 个文件删除失败";
            }

            return ApiResponse::success([
                'deleted_count' => $deletedCount,
                'errors' => $errors,
                'summary' => [
                    'total_requested' => count($ids),
                    'deleted' => $deletedCount,
                    'failed' => count($errors)
                ]
            ], $message);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('请求参数验证失败', 422, $e->errors());
        } catch (\Exception $e) {
            Log::error('Batch file deletion controller error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_params' => $request->all()
            ]);

            return ApiResponse::error('批量删除文件失败：' . $e->getMessage(), 500);
        }
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
