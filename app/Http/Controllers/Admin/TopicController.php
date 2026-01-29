<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TopicStoreRequest;
use App\Http\Requests\Admin\TopicUpdateRequest;
use App\Http\Requests\Admin\TopicStatusRequest;
use App\Http\Requests\Admin\TopicRecommendRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\Admin\TopicResource;
use App\Http\Resources\Admin\TopicListResource;
use App\Http\Resources\Admin\TopicSimpleResource;
use App\Services\Admin\TopicService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TopicController extends Controller
{
    /**
     * @var TopicService
     */
    protected $topicService;

    /**
     * TopicController constructor.
     *
     * @param TopicService $topicService
     */
    public function __construct(TopicService $topicService)
    {
        $this->topicService = $topicService;
    }

    /**
     * 话题列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request)
    {
        $filters = [
            'topicName' => $request->input('topicName'),
            'status' => $request->input('status'),
            'isRecommend' => $request->input('isRecommend'),
            'isOfficial' => $request->input('isOfficial'),
            'beginTime' => $request->input('beginTime'),
            'endTime' => $request->input('endTime'),
        ];

        $pageNum = (int) $request->input('pageNum', 1);
        $pageSize = (int) $request->input('pageSize', 10);

        $paginator = $this->topicService->getList($filters, $pageNum, $pageSize);

        return ApiResponse::paginate($paginator, TopicListResource::class, '查询成功');
    }

    /**
     * 话题详情
     *
     * @param int $topicId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($topicId)
    {
        $topic = $this->topicService->getDetail((int) $topicId);

        if (!$topic) {
            return ApiResponse::error('话题不存在');
        }

        return ApiResponse::resource($topic, TopicResource::class, '查询成功');
    }

    /**
     * 新增话题
     *
     * @param TopicStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(TopicStoreRequest $request)
    {
        try {
            $data = [
                'topicName' => $request->input('topicName'),
                'coverUrl' => $request->input('coverUrl'),
                'description' => $request->input('description'),
                'detailHtml' => $request->input('detailHtml'),
                'sortNum' => $request->input('sortNum'),
                'isRecommend' => $request->input('isRecommend'),
                'isOfficial' => $request->input('isOfficial'),
                'status' => $request->input('status'),
            ];

            $this->topicService->create($data);

            return ApiResponse::success([], '新增成功');
        } catch (\Exception $e) {
            Log::error('新增话题失败', [
                'action' => 'store',
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 更新话题
     *
     * @param TopicUpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(TopicUpdateRequest $request)
    {
        try {
            $topicId = (int) $request->input('topicId');
            $data = [
                'topicName' => $request->input('topicName'),
                'coverUrl' => $request->input('coverUrl'),
                'description' => $request->input('description'),
                'detailHtml' => $request->input('detailHtml'),
                'sortNum' => $request->input('sortNum'),
                'isRecommend' => $request->input('isRecommend'),
                'isOfficial' => $request->input('isOfficial'),
                'status' => $request->input('status'),
            ];

            $result = $this->topicService->update($topicId, $data);

            if (!$result) {
                return ApiResponse::error('话题不存在');
            }

            return ApiResponse::success([], '修改成功');
        } catch (\Exception $e) {
            Log::error('更新话题失败', [
                'action' => 'update',
                'topic_id' => $request->input('topicId'),
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 删除话题（支持批量）
     *
     * @param string $topicIds 逗号分隔的话题ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($topicIds)
    {
        try {
            $ids = array_map('intval', explode(',', $topicIds));

            $deletedCount = $this->topicService->delete($ids);

            if ($deletedCount > 0) {
                return ApiResponse::success([], '删除成功');
            } else {
                return ApiResponse::error('删除失败，话题不存在');
            }
        } catch (\Exception $e) {
            Log::error('删除话题失败', [
                'action' => 'destroy',
                'topic_ids' => $topicIds,
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 修改话题状态
     *
     * @param TopicStatusRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeStatus(TopicStatusRequest $request)
    {
        try {
            $topicId = (int) $request->input('topicId');
            $status = (int) $request->input('status');

            $result = $this->topicService->changeStatus($topicId, $status);

            if (!$result) {
                return ApiResponse::error('话题不存在');
            }

            return ApiResponse::success([], '修改成功');
        } catch (\Exception $e) {
            Log::error('修改话题状态失败', [
                'action' => 'changeStatus',
                'topic_id' => $request->input('topicId'),
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 设置推荐状态
     *
     * @param TopicRecommendRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeRecommend(TopicRecommendRequest $request)
    {
        try {
            $topicId = (int) $request->input('topicId');
            $isRecommend = (int) $request->input('isRecommend');

            $result = $this->topicService->changeRecommend($topicId, $isRecommend);

            if (!$result) {
                return ApiResponse::error('话题不存在');
            }

            return ApiResponse::success([], '修改成功');
        } catch (\Exception $e) {
            Log::error('设置话题推荐状态失败', [
                'action' => 'changeRecommend',
                'topic_id' => $request->input('topicId'),
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 下拉选项列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function optionselect()
    {
        $options = $this->topicService->getOptions();

        return ApiResponse::collection($options, TopicSimpleResource::class, '查询成功');
    }
}
