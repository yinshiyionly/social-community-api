<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 搜索全部 - 用户资源类
 */
class SearchAllUserResource extends JsonResource
{
    /**
     * 是否已关注（外部注入）
     *
     * @var bool
     */
    public $isFollowed = false;

    /**
     * 设置是否已关注
     *
     * @param bool $isFollowed
     * @return $this
     */
    public function setIsFollowed(bool $isFollowed)
    {
        $this->isFollowed = $isFollowed;
        return $this;
    }

    /**
     * 转换资源为数组
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => 'user-' . $this->member_id,
            'name' => $this->nickname ?? '',
            'avatar' => $this->avatar ?? '',
            'fans' => $this->fans_count ?? 0,
            'isFollowed' => $this->isFollowed,
        ];
    }
}
