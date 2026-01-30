<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 热门话题列表资源 - 用于发帖时选择话题
 */
class TopicHotListResource extends JsonResource
{
    /**
     * 排名序号
     *
     * @var int
     */
    protected int $rank;

    /**
     * 设置排名
     *
     * @param int $rank
     * @return $this
     */
    public function setRank(int $rank): self
    {
        $this->rank = $rank;
        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $viewCount = $this->stat ? $this->stat->view_count : 0;

        return [
            'id' => $this->topic_id,
            'rank' => $this->rank ?? 0,
            'name' => $this->topic_name,
            'views' => $this->formatViewCount($viewCount),
        ];
    }

    /**
     * 格式化浏览量
     *
     * @param int $count
     * @return string
     */
    protected function formatViewCount(int $count): string
    {
        if ($count >= 100000000) {
            return sprintf('%.1f亿次浏览', $count / 100000000);
        }

        if ($count >= 10000) {
            return sprintf('%.1f万次浏览', $count / 10000);
        }

        return $count . '次浏览';
    }
}
