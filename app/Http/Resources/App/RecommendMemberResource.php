<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 推荐用户资源类 - 用于推荐用户列表展示
 */
class RecommendMemberResource extends JsonResource
{
    /**
     * 转换资源为数组
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'memberId' => $this['member_id'],
            'nickname' => $this['nickname'] ?? '',
            'avatar' => $this['avatar'] ?? '',
            'fansCount' => $this['fans_count_text'] ?? '0粉丝',
            'isFollowed' => $this['is_followed'] ?? false,
        ];
    }
}
