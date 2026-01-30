<?php

namespace App\Services\App;

use App\Models\App\AppTopicBase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * App 端话题服务类
 */
class TopicService
{
    /**
     * 获取热门话题列表（发帖选择用）
     *
     * 排序规则：is_recommend 降序 -> sort_num 降序 -> topic_id 降序
     *
     * @param int $limit 限制数量
     * @return Collection
     */
    public function getHotTopicList(int $limit = 50): Collection
    {
        return AppTopicBase::query()
            ->select(['topic_id', 'topic_name', 'is_recommend', 'sort_num'])
            ->with('stat:topic_id,view_count')
            ->normal()
            ->orderByDesc('is_recommend')
            ->orderByDesc('sort_num')
            ->orderByDesc('topic_id')
            ->limit($limit)
            ->get();
    }
}
