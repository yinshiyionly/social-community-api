<?php

namespace App\Services\Admin;

use App\Models\App\AppVideoBaijiayun;
use App\Services\BaijiayunLiveService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BaijiayunVideoService
{
    /**
     * 获取视频列表（分页）
     *
     * @param array $filters
     * @param int $pageNum
     * @param int $pageSize
     * @return LengthAwarePaginator
     */
    public function getList(array $filters, int $pageNum = 1, int $pageSize = 10): LengthAwarePaginator
    {
        $query = AppVideoBaijiayun::query()
            ->select([
                'id', 'video_id', 'name', 'status', 'publish_status',
                'total_size', 'preface_url', 'play_url',
                'length', 'width', 'height',
                'created_at', 'updated_at',
            ]);

        if (isset($filters['videoId']) && $filters['videoId'] !== '') {
            $query->where('video_id', (int)$filters['videoId']);
        }

        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', (int)$filters['status']);
        }

        if (isset($filters['publishStatus']) && $filters['publishStatus'] !== '') {
            $query->where('publish_status', (int)$filters['publishStatus']);
        }

        if (!empty($filters['beginTime'])) {
            $query->where('created_at', '>=', $filters['beginTime']);
        }

        if (!empty($filters['endTime'])) {
            $query->where('created_at', '<=', $filters['endTime']);
        }

        $query->orderByDesc('created_at')->orderByDesc('id');

        return $query->paginate($pageSize, ['*'], 'pageNum', $pageNum);
    }

    /**
     * 获取视频详情
     *
     * @param int $videoId
     * @return AppVideoBaijiayun|null
     */
    public function getDetail(int $videoId): ?AppVideoBaijiayun
    {
        return AppVideoBaijiayun::query()
            ->where('video_id', $videoId)
            ->first();
    }

    /**
     * 检查 videoId 是否存在
     *
     * @param int $videoId
     * @return bool
     */
    public function existsByVideoId(int $videoId): bool
    {
        return AppVideoBaijiayun::query()
            ->where('video_id', $videoId)
            ->exists();
    }

    /**
     * 创建视频
     *
     * @param array $data
     * @return AppVideoBaijiayun
     */
    public function create(array $data): AppVideoBaijiayun
    {
        return AppVideoBaijiayun::query()->create([
            'video_id' => (int)$data['videoId'],
            'name' => $data['name'],
            'status' => isset($data['status']) ? (int)$data['status'] : AppVideoBaijiayun::STATUS_UPLOADING,
            'total_size' => isset($data['totalSize']) ? (string)$data['totalSize'] : '0',
            'preface_url' => $data['prefaceUrl'] ?? null,
            'play_url' => $data['playUrl'] ?? null,
            'length' => isset($data['length']) ? (int)$data['length'] : 0,
            'width' => isset($data['width']) ? (int)$data['width'] : 0,
            'height' => isset($data['height']) ? (int)$data['height'] : 0,
            'publish_status' => isset($data['publishStatus'])
                ? (int)$data['publishStatus']
                : AppVideoBaijiayun::PUBLISH_STATUS_UNPUBLISHED,
        ]);
    }

    /**
     * 更新视频
     *
     * @param int $videoId
     * @param array $data
     * @return bool
     */
    public function update(int $videoId, array $data): bool
    {
        $video = AppVideoBaijiayun::query()
            ->where('video_id', $videoId)
            ->first();

        if (!$video) {
            return false;
        }

        $updateData = [];

        if (array_key_exists('name', $data)) {
            $updateData['name'] = $data['name'];
        }
        if (array_key_exists('status', $data)) {
            $updateData['status'] = (int)$data['status'];
        }
        if (array_key_exists('totalSize', $data)) {
            $updateData['total_size'] = (string)$data['totalSize'];
        }
        if (array_key_exists('prefaceUrl', $data)) {
            $updateData['preface_url'] = $data['prefaceUrl'];
        }
        if (array_key_exists('playUrl', $data)) {
            $updateData['play_url'] = $data['playUrl'];
        }
        if (array_key_exists('length', $data)) {
            $updateData['length'] = (int)$data['length'];
        }
        if (array_key_exists('width', $data)) {
            $updateData['width'] = (int)$data['width'];
        }
        if (array_key_exists('height', $data)) {
            $updateData['height'] = (int)$data['height'];
        }
        if (array_key_exists('publishStatus', $data)) {
            $updateData['publish_status'] = (int)$data['publishStatus'];
        }

        if (empty($updateData)) {
            return true;
        }

        return $video->update($updateData);
    }

    /**
     * 删除单个视频（软删除）。
     *
     * @param int $videoId 视频 ID
     * @return int 受影响行数，0 表示记录不存在或已删除
     */
    public function delete(int $videoId): int
    {
        return AppVideoBaijiayun::query()
            ->where('video_id', $videoId)
            ->delete();
    }

    /**
     * 判断视频是否已被课程章节引用。
     *
     * @param int $videoId 视频 ID
     * @return bool true 表示已被使用，禁止删除
     */
    public function isVideoUsed(int $videoId): bool
    {
        return DB::table('admin_video_chapter_content')
            ->where('video_id', $videoId)
            ->exists();
    }

    /**
     * 上传点播视频
     *
     * @param $file
     * @param $fileName
     * @param $fileSizeBytes
     * @return array|\Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function uploadVideo($file, $fileName, $fileSizeBytes)
    {
        // 1. 获取百家云-点播视频上传地址
        $service = new BaijiayunLiveService();
        $params = [
            'fileName' => $fileName ?? '', // 文件名
            'definition' => 2, // 目标清晰度(16:标清 1:高清 2:超清 4:720p 8:1080p 多种清晰度用英文逗号分隔)
            'audioWithView' => 0, // 是否作为音频处理 0：否 1：是
            'format' => 'mp4', // 转码格式（1:mp4 2:flv 4:m3u8 多种格式用英文逗号分隔）默认是3种格式都转
            'callbackUrl' => env('APP_URL') . '/api/admin/video/baijiayun/callback',
        ];
        // 2. 调用百家云服务获取文件上传地址
        $getUploadUrlResult = $service->videoGetUploadUrl($params);

        if (!$getUploadUrlResult['success']) {
            throw new \Exception('获取上传地址失败: ' . $getUploadUrlResult['error_message']);
        }

        if ($getUploadUrlResult['error_code'] != 0) {
            throw new \Exception('获取上传地址失败: ' . $getUploadUrlResult['error_message']);
        }
        // 3. 解析
        $uploadInfo = $getUploadUrlResult['data'];

        // 4. 上传视频文件到百家云
        $response = Http::attach(
            'data',
            file_get_contents($file->getRealPath()),
            $file->getClientOriginalName()
        )->post($uploadInfo['upload_url']);

        // 解析响应
        $responseData = $response->json();

        // 检查上传是否成功
        if (isset($responseData['code']) && $responseData['code'] == 1) {
            // 返回视频ID、上传结果和文件信息
            $resultData = [
                'video_id' => $uploadInfo['video_id'],
                'name' => $fileName,
                "total_size" => $fileSizeBytes,
                'status' => '10', // 10:上传成功
            ];
            $videoService = new BaijiayunVideoService();
            $videoService->create([
                'videoId' => $uploadInfo['video_id'],
                'name' => $fileName,
                'status' => AppVideoBaijiayun::STATUS_UPLOADING,
                'totalSize' => $fileSizeBytes,
                'prefaceUrl' => null,
                'playUrl' => null,
                'length' => 0,
                'width' => 0,
                'height' => 0,
                'publishStatus' => AppVideoBaijiayun::PUBLISH_STATUS_UNPUBLISHED,
            ]);
            return $resultData;
        } else {
            Log::error('百家云-上传视频失败', ['responseData' => $responseData]);
            return [];
        }
    }
}
