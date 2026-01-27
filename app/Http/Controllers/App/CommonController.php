<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Resources\App\AppApiResponse;
use App\Services\AppFileUploadService;
use App\Exceptions\FileUpload\FileValidationException;
use App\Exceptions\FileUpload\FileUploadException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CommonController extends Controller
{
    /**
     * @var AppFileUploadService
     */
    protected AppFileUploadService $fileUploadService;

    public function __construct(AppFileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * 上传单张图片
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImage(Request $request)
    {
        $memberId = $request->attributes->get('member_id');

        if (!$request->hasFile('file')) {
            return AppApiResponse::error('请选择要上传的图片');
        }

        $file = $request->file('file');

        // 验证是否为图片
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return AppApiResponse::error('仅支持上传图片文件');
        }

        try {
            $result = $this->fileUploadService->upload($file, $memberId);

            return AppApiResponse::success([
                'data' => [
                    'fileName' => $result['original_name'],
                    'key' => $result['path'],
                    'reused' => $result['reused'] ?? false,
                    'size' => $result['file_size'],
                    'url' => $result['url']
                ]
            ]);
        } catch (FileValidationException $e) {
            return AppApiResponse::error('文件验证失败,请重试');
        } catch (FileUploadException $e) {
            Log::error('Image upload failed', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);
            return AppApiResponse::serverError();
        } catch (\Exception $e) {
            Log::error('Image upload unexpected error', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);
            return AppApiResponse::serverError();
        }
    }

    /**
     * 上传视频
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadVideo(Request $request)
    {
        $memberId = $request->attributes->get('member_id');
        if (!$request->hasFile('file')) {
            return AppApiResponse::error('请选择要上传的视频');
        }

        $file = $request->file('file');

        // 验证是否为视频
        $allowedMimes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv', 'video/webm', 'video/mpeg'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return AppApiResponse::error('仅支持上传视频文件');
        }

        try {
            $result = $this->fileUploadService->upload($file, $memberId);

            return AppApiResponse::success([
                'data' => [
                    'fileName' => $result['original_name'],
                    'key' => $result['path'],
                    'reused' => $result['reused'] ?? false,
                    'size' => $result['file_size'],
                    'url' => $result['url'],
                ]
            ]);
        } catch (FileValidationException $e) {
            return AppApiResponse::error('文件验证失败,请重试');
        } catch (FileUploadException $e) {
            Log::error('Video upload failed', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);
            return AppApiResponse::serverError();
        } catch (\Exception $e) {
            Log::error('Video upload unexpected error', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);
            return AppApiResponse::serverError();
        }
    }

    /**
     * 上传多张图片
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImages(Request $request)
    {
        $memberId = $request->attributes->get('member_id');

        // 支持 files[] 和 files 两种形式
        $files = $request->file('files');

        if (empty($files)) {
            return AppApiResponse::error('请选择要上传的图片');
        }

        // 确保 files 是数组
        if (!is_array($files)) {
            $files = [$files];
        }

        // 过滤掉空值
        $files = array_filter($files, function ($file) {
            return $file !== null;
        });

        if (empty($files)) {
            return AppApiResponse::error('请选择要上传的图片');
        }

        // 重新索引数组
        $files = array_values($files);

        // 验证是否都为图片
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
        foreach ($files as $file) {
            if (!in_array($file->getMimeType(), $allowedMimes)) {
                return AppApiResponse::error('仅支持上传图片文件');
            }
        }

        try {
            $batchResult = $this->fileUploadService->uploadMultiple($files, $memberId, [
                'max_files' => 9,
            ]);

            // 构建响应数据
            $results = [];
            $errors = [];

            // 处理成功的上传
            foreach ($batchResult['success'] as $index => $item) {
                $results[] = [
                    'fileName' => $item['original_name'],
                    'index' => $index,
                    'key' => $item['path'],
                    'reused' => $item['reused'] ?? false,
                    'size' => $item['file_size'],
                    'url' => $item['url'],
                ];
            }

            // 处理失败的上传
            foreach ($batchResult['failed'] as $failedItem) {
                $errors[] = [
                    'index' => $failedItem['index'],
                    'fileName' => $failedItem['original_name'] ?? '',
                    'error' => '上传失败',
                ];
            }

            return AppApiResponse::success([
                'data' => [
                    'errors' => $errors,
                    'failed' => count($errors),
                    'results' => $results,
                    'success' => count($results),
                    'total' => count($files)
                ]
            ]);
        } catch (FileValidationException $e) {
            return AppApiResponse::error('文件验证失败,请重试');
        } catch (FileUploadException $e) {
            Log::error('Multiple images upload failed', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);
            return AppApiResponse::serverError();
        } catch (\Exception $e) {
            Log::error('Multiple images upload unexpected error', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);
            return AppApiResponse::serverError();
        }
    }
}
