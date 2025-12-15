<?php

declare(strict_types=1);

namespace App\Http\Controllers\Insight;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Jobs\Detection\Task\DetectionTaskWarnJob;
use App\Models\Insight\InsightPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 舆情数据同步控制器
 *
 * 接收来自 Python Kafka 消费者的舆情数据，解析并存储到数据库
 */
class InsightSyncController extends Controller
{
    /**
     * 接收舆情数据同步请求
     *
     * 解析 item_doc 数据，映射到 InsightPost Model，使用 upsert 逻辑存储
     *
     * @route POST /api/insight/sync
     * @param Request $request
     * @return JsonResponse
     */
    public function sync(Request $request): JsonResponse
    {
        // 获取 item_doc 数据
        $itemDoc = $request->input('item_doc');

        // 验证 item_doc 是否存在
        if (empty($itemDoc)) {
            return ApiResponse::error('Missing item_doc', 400, 400);
        }

        // 验证必要字段 origin_id
        if (empty($itemDoc['origin_id'])) {
            return ApiResponse::error('Missing origin_id in item_doc', 422, 422);
        }

        try {
            // 解析并映射数据
            $data = $this->parseItemDoc($itemDoc);

            // 使用 updateOrCreate 实现 upsert 逻辑
            // 根据 origin_id 查找记录，存在则更新，不存在则创建
            InsightPost::updateOrCreate(
                ['origin_id' => $data['origin_id']],
                $data
            );

            // 异步队列处理具体的负向数据并发送预警邮件
            $jobParams = [
                'origin_id' => $data['origin_id'],
                'sentiment' => $data['sentiment']
            ];
            DetectionTaskWarnJob::dispatch($jobParams);

            return ApiResponse::success([], '数据同步成功');
        } catch (Throwable $e) {
            // 记录错误日志
            Log::error('Insight sync failed', [
                'origin_id' => $itemDoc['origin_id'],
                'error' => $e->getMessage()
            ]);

            return ApiResponse::error('Database error: ' . $e->getMessage(), 500, 500);
        }
    }


    /**
     * 解析 item_doc 数据并映射到数据库字段
     *
     * @param array $itemDoc Kafka 消息中的 item_doc 数据
     * @return array 映射后的数据库字段数组
     */
    private function parseItemDoc(array $itemDoc): array
    {
        // 处理 matched_task_ids：数组转字符串
        /*$matchedTaskIds = null;
        if (isset($itemDoc['matched_task_ids'])) {
            if (is_array($itemDoc['matched_task_ids'])) {
                $matchedTaskIds = implode(',', $itemDoc['matched_task_ids']);
            } else {
                $matchedTaskIds = (string)$itemDoc['matched_task_ids'];
            }
        }*/

        // 映射字段
        return [
            'origin_id' => $itemDoc['origin_id'],
            'post_id' => $itemDoc['post_id'] ?? '',
            'publish_time' => $this->parseDateTime($itemDoc['publish_time'] ?? null),
            'push_ready_time' => $this->parseDateTime($itemDoc['push_ready_time'] ?? null),
            'main_domain' => $itemDoc['main_domain'] ?? '',
            'domain' => $itemDoc['domain'] ?? '',
            'url' => $itemDoc['url'] ?? '',
            'title' => $itemDoc['title'] ?? null,
            'feature' => $itemDoc['feature'] ?? null,
            'sentiment' => $itemDoc['feature']['sentiment'] ?? 0,
            'poi' => $itemDoc['poi'] ?? null,
            'status' => $itemDoc['status'] ?? 1,
            'post_type' => $itemDoc['post_type'] ?? 0,
            'video_info' => $itemDoc['video_info'] ?? null,
            'based_location' => $itemDoc['based_location'] ?? null,
            'matched_task_ids' => $itemDoc['matched_task_ids'] ?? [],
            'process_state' => InsightPost::PROCESS_STATE_PENDING,
        ];
    }

    /**
     * 解析日期时间字段
     *
     * 支持时间戳（秒/毫秒）和日期字符串格式
     *
     * @param mixed $value 日期时间值
     * @return string|null 格式化后的日期时间字符串
     */
    private function parseDateTime($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // 如果是数字（时间戳）
        if (is_numeric($value)) {
            $timestamp = (int)$value;
            // 如果是毫秒时间戳，转换为秒
            if ($timestamp > 9999999999) {
                $timestamp = (int)($timestamp / 1000);
            }
            return date('Y-m-d H:i:s', $timestamp);
        }

        // 如果是字符串，尝试解析
        if (is_string($value)) {
            $time = strtotime($value);
            if ($time !== false) {
                return date('Y-m-d H:i:s', $time);
            }
        }

        return null;
    }
}
