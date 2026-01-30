<?php

namespace App\Services\Admin;

use App\Models\App\AppTopicBase;
use App\Models\App\AppTopicStat;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TopicService
{
    /**
     * 获取话题列表（分页）
     *
     * @param array $filters 筛选条件
     * @param int $pageNum 页码
     * @param int $pageSize 每页数量
     * @return LengthAwarePaginator
     */
    public function getList(array $filters, int $pageNum = 1, int $pageSize = 10): LengthAwarePaginator
    {
        $query = AppTopicBase::query()->with('stat');

        // 话题名称搜索
        if (!empty($filters['topicName'])) {
            $query->where('topic_name', 'like', '%' . $filters['topicName'] . '%');
        }

        // 状态筛选
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        // 推荐状态筛选
        if (isset($filters['isRecommend']) && $filters['isRecommend'] !== '') {
            $query->where('is_recommend', $filters['isRecommend']);
        }

        // 官方话题筛选
        if (isset($filters['isOfficial']) && $filters['isOfficial'] !== '') {
            $query->where('is_official', $filters['isOfficial']);
        }

        // 时间范围筛选
        if (!empty($filters['beginTime'])) {
            $query->where('created_at', '>=', $filters['beginTime']);
        }
        if (!empty($filters['endTime'])) {
            $query->where('created_at', '<=', $filters['endTime']);
        }

        // 排序
        $query->orderByDesc('sort_num')->orderByDesc('topic_id');

        return $query->paginate($pageSize, ['*'], 'pageNum', $pageNum);
    }

    /**
     * 获取话题详情
     *
     * @param int $topicId
     * @return AppTopicBase|null
     */
    public function getDetail(int $topicId): ?AppTopicBase
    {
        return AppTopicBase::query()
            ->with('stat')
            ->where('topic_id', $topicId)
            ->first();
    }

    /**
     * 创建话题
     *
     * @param array $data
     * @return AppTopicBase
     */
    public function create(array $data): AppTopicBase
    {
        DB::beginTransaction();
        try {
            // 创建话题
            $topic = AppTopicBase::create([
                'topic_name' => $data['topicName'],
                'cover_url' => $data['coverUrl'] ?? '',
                'description' => $data['description'] ?? '',
                'detail_html' => $data['detailHtml'] ?? null,
                'creator_id' => $data['creatorId'] ?? 0,
                'sort_num' => $data['sortNum'] ?? 0,
                'is_recommend' => $data['isRecommend'] ?? AppTopicBase::IS_RECOMMEND_NO,
                'is_official' => $data['isOfficial'] ?? AppTopicBase::IS_OFFICIAL_NO,
                'status' => $data['status'],
            ]);

            // 同步创建统计记录
            AppTopicStat::create([
                'topic_id' => $topic->topic_id,
                'post_count' => 0,
                'view_count' => 0,
                'follow_count' => 0,
                'participant_count' => 0,
                'today_post_count' => 0,
                'heat_score' => 0,
            ]);

            DB::commit();

            // 重新加载关联
            $topic->load('stat');

            return $topic;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('创建话题失败', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 更新话题
     *
     * @param int $topicId
     * @param array $data
     * @return bool
     */
    public function update(int $topicId, array $data): bool
    {
        $topic = AppTopicBase::query()->where('topic_id', $topicId)->first();

        if (!$topic) {
            return false;
        }

        $updateData = [];

        if (isset($data['topicName'])) {
            $updateData['topic_name'] = $data['topicName'];
        }
        if (array_key_exists('coverUrl', $data)) {
            $updateData['cover_url'] = $data['coverUrl'] ?? '';
        }
        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description'] ?? '';
        }
        if (array_key_exists('detailHtml', $data)) {
            $updateData['detail_html'] = $data['detailHtml'];
        }
        if (isset($data['sortNum'])) {
            $updateData['sort_num'] = $data['sortNum'];
        }
        if (isset($data['isRecommend'])) {
            $updateData['is_recommend'] = $data['isRecommend'];
        }
        if (isset($data['isOfficial'])) {
            $updateData['is_official'] = $data['isOfficial'];
        }
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        return $topic->update($updateData);
    }

    /**
     * 删除话题（支持批量，软删除）
     *
     * @param array $topicIds
     * @return int 删除数量
     */
    public function delete(array $topicIds): int
    {
        return AppTopicBase::query()
            ->whereIn('topic_id', $topicIds)
            ->delete();
    }

    /**
     * 修改话题状态
     *
     * @param int $topicId
     * @param int $status
     * @return bool
     */
    public function changeStatus(int $topicId, int $status): bool
    {
        return AppTopicBase::query()
                ->where('topic_id', $topicId)
                ->update(['status' => $status]) > 0;
    }

    /**
     * 修改推荐状态
     *
     * @param int $topicId
     * @param int $isRecommend
     * @return bool
     */
    public function changeRecommend(int $topicId, int $isRecommend): bool
    {
        return AppTopicBase::query()
                ->where('topic_id', $topicId)
                ->update(['is_recommend' => $isRecommend]) > 0;
    }

    /**
     * 获取下拉选项列表（只返回正常状态的话题）
     *
     * @return Collection
     */
    public function getOptions(): Collection
    {
        return AppTopicBase::query()
            ->select(['topic_id', 'topic_name'])
            ->where('status', AppTopicBase::STATUS_NORMAL)
            ->orderByDesc('sort_num')
            ->orderByDesc('topic_id')
            ->get();
    }

    /**
     * 检查话题是否存在
     *
     * @param int $topicId
     * @return bool
     */
    public function exists(int $topicId): bool
    {
        return AppTopicBase::query()
            ->where('topic_id', $topicId)
            ->exists();
    }
}
