<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BaijiayunVideoStoreRequest;
use App\Http\Requests\Admin\BaijiayunVideoUpdateRequest;
use App\Http\Resources\Admin\BaijiayunVideoListResource;
use App\Http\Resources\Admin\BaijiayunVideoResource;
use App\Http\Resources\ApiResponse;
use App\Models\App\AppVideoBaijiayun;
use App\Services\Admin\BaijiayunVideoService;
use App\Services\BaijiayunLiveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BaijiayunVideoController extends Controller
{
    /**
     * @var BaijiayunVideoService
     */
    protected $baijiayunVideoService;

    /**
     * @param BaijiayunVideoService $baijiayunVideoService
     */
    public function __construct(BaijiayunVideoService $baijiayunVideoService)
    {
        $this->baijiayunVideoService = $baijiayunVideoService;
    }

    /**
     * 常量选项
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function constants()
    {
        $data = [
            'statusOptions' => AppVideoBaijiayun::getStatusOptions(),
            'publishStatusOptions' => AppVideoBaijiayun::getPublishStatusOptions(),
            'sourceOptions' => [
                [
                    'label' => '百家云',
                    'value' => AppVideoBaijiayun::SOURCE_BAIJIAYUN,
                ],
            ],
        ];

        return ApiResponse::success(['data' => $data], '查询成功');
    }

    /**
     * 视频列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request)
    {
        $filters = [
            'videoId' => $request->input('videoId'),
            'name' => $request->input('name'),
            'status' => $request->input('status'),
            'publishStatus' => $request->input('publishStatus'),
            'beginTime' => $request->input('beginTime'),
            'endTime' => $request->input('endTime'),
        ];

        $pageNum = (int)$request->input('pageNum', 1);
        $pageSize = (int)$request->input('pageSize', 10);

        $paginator = $this->baijiayunVideoService->getList($filters, $pageNum, $pageSize);

        return ApiResponse::paginate($paginator, BaijiayunVideoListResource::class, '查询成功');
    }

    /**
     * 视频详情
     *
     * @param int $videoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($videoId)
    {
        $video = $this->baijiayunVideoService->getDetail((int)$videoId);

        if (!$video) {
            return ApiResponse::error('视频不存在');
        }

        return ApiResponse::resource($video, BaijiayunVideoResource::class, '查询成功');
    }

    /**
     * 上传视频到百家云
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadVideo(Request $request)
    {
        // 验证参数
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:1024000', // 只允许mp4格式，最大1000MB
            'file_name' => 'nullable|string|max:255',
        ], [
            'file.required' => '请选择要上传的文件',
            // 'file.mimes' => '只支持MP4格式的视频文件',
            'file.max' => '文件大小不能超过1000MB',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }

        try {
            // 1. 获取上传文件
            $file = $request->file('file');

            // 2. 获取文件大小（字节）
            $fileSizeBytes = $file->getSize();
            // 3. 如果没有提供文件名，则使用原始文件名
            $fileName = $request->file_name ?? $file->getClientOriginalName();

            // 4. 上传视频
            $this->baijiayunVideoService->uploadVideo($file, $fileName, $fileSizeBytes);

            return ApiResponse::success();
        } catch (\Exception $e) {
            return ApiResponse::error('视频上传异常: ' . $e->getMessage());
        }
    }

    /**
     * 新增视频
     *
     * @param BaijiayunVideoStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(BaijiayunVideoStoreRequest $request)
    {
        try {
            $data = $request->validated();
            $videoId = (int)$data['videoId'];

            if ($this->baijiayunVideoService->existsByVideoId($videoId)) {
                return ApiResponse::error('视频ID已存在');
            }

            $video = $this->baijiayunVideoService->create($data);

            return ApiResponse::success([
                'data' => [
                    'videoId' => (int)$video->video_id,
                ],
            ], '新增成功');
        } catch (\Exception $e) {
            Log::error('新增百家云视频失败', [
                'action' => 'store',
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 更新视频
     *
     * @param BaijiayunVideoUpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(BaijiayunVideoUpdateRequest $request)
    {
        try {
            $data = $request->validated();
            $videoId = (int)$data['videoId'];
            unset($data['videoId']);

            $result = $this->baijiayunVideoService->update($videoId, $data);

            if (!$result) {
                return ApiResponse::error('视频不存在');
            }

            return ApiResponse::success([], '修改成功');
        } catch (\Exception $e) {
            Log::error('更新百家云视频失败', [
                'action' => 'update',
                'video_id' => $request->input('videoId'),
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 删除视频（支持批量）
     *
     * @param string $videoIds
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($videoIds)
    {
        try {
            $ids = array_map('intval', explode(',', (string)$videoIds));
            $ids = array_values(array_filter(array_unique($ids), function ($id) {
                return $id > 0;
            }));

            if (empty($ids)) {
                return ApiResponse::error('参数错误');
            }

            $usedVideoIds = $this->baijiayunVideoService->getUsedVideoIds($ids);
            if (!empty($usedVideoIds)) {
                return ApiResponse::error('视频已被课程章节使用，无法删除');
            }

            $deletedCount = $this->baijiayunVideoService->delete($ids);
            if ($deletedCount <= 0) {
                return ApiResponse::error('删除失败，视频不存在');
            }

            return ApiResponse::success([], '删除成功');
        } catch (\Exception $e) {
            Log::error('删除百家云视频失败', [
                'action' => 'destroy',
                'video_ids' => $videoIds,
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 点播视频转码回调
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function callback(Request $request)
    {
        $params = $request->all();
        // 参数中没有 video_id
        if (!isset($params['video_id'])) {
            return response()->json(['code' => 0]);
        }
        // 参数中没有携带 status
        if (!isset($params['status'])) {
            return response()->json(['code' => 0]);
        }
        switch ((int)$params['status']) {
            case 20:
                Log::info('点播视频上传成功', ['params' => $params]);
                break;
            case 30:
                Log::error('点播视频转码失败', ['params' => $params]);
                break;
            case 100:
                Log::info('点播视频转码成功', ['params' => $params]);
                // 1. 调用接口获取播放器token
                $service = new BaijiayunLiveService();
                $playToken = $service->videoGetPlayerToken((int)$params['video_id']);
                if (empty($playToken['success']) || empty($playToken['data']['token'])) {
                    return response()->json(['code' => 0]);
                }
                // 2. 组建 play_url 地址
                $playUrl = sprintf(
                    "https://%s.at.baijiayun.com/web/video/player?vid=%s&token=%s&player=bplayer",
                    env('BAIJIAYUN_PRIVATE_DOMAIN'),
                    $params['video_id'],
                    $playToken['data']['token']
                );
                $update = [
                    'play_url' => $playUrl,
                    'preface_url' => $params['preface_url'] ?? '',
                    'total_size' => $params['total_size'] ?? 0,
                    'status' => $params['status'] ?? 20,
                    'length' => $params['length'] ?? 0,
                    // TODO 后面启用 md5
                    // 'file_md5' => $params['file_md5'] ?? ''
                ];
                try {
                    AppVideoBaijiayun::query()
                        ->where(['video_id' => $params['video_id']])
                        ->update($update);
                } catch (\Exception $e) {
                    Log::error('点播视频转码更新数据表失败' . $e->getMessage(), ['params' => $params, 'update' => $update]);
                }
                break;
        }
        return response()->json(['code' => 0]);
    }
}

