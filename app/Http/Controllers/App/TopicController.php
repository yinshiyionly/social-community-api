<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Resources\App\AppApiResponse;
use App\Http\Resources\App\TopicHotListResource;
use App\Services\App\TopicService;
use Illuminate\Support\Facades\Log;

/**
 * App 端话题控制器
 */
class TopicController extends Controller
{
    /**
     * @var TopicService
     */
    protected TopicService $topicService;

    public function __construct(TopicService $topicService)
    {
        $this->topicService = $topicService;
    }

    /**
     * 获取热门话题列表（发帖选择用）
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function hotList()
    {
        try {
            $topics = $this->topicService->getHotTopicList();

            // 手动设置排名并转换为数组
            $list = $topics->map(function ($topic, $index) {
                return (new TopicHotListResource($topic))
                    ->setRank($index + 1)
                    ->resolve();
            })->values()->all();

            return AppApiResponse::success([
                'data' => [
                    'list' => $list,
                    'total' => count($list),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('获取热门话题列表失败', [
                'error' => $e->getMessage(),
            ]);
            return AppApiResponse::serverError();
        }
    }
}
