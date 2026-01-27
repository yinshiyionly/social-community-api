<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 会员搜索资源类 - 用于搜索结果展示
 */
class MemberSearchResource extends JsonResource
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
            'memberId' => $this->member_id,
            'avatar' => $this->avatar ?? '',
            'nickname' => $this->nickname ?? '',
            'followerCount' => $this->follower_count ?? 0,
        ];
    }
}
