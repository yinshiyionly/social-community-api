<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 用户主页资源类 - 用于个人主页详情展示
 */
class MemberProfileResource extends JsonResource
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
            'nickname' => $this->nickname ?? '',
            'avatar' => $this->avatar ?? '',
            // 用户主页背景图片地址
            'bgImage' => $this->bgImage ?? '',
            'bio' => $this->bio ?? '',
            'fansCount' => $this->fans_count ?? 0,
            'followCount' => $this->following_count ?? 0,
            'likeCount' => $this->like_count ?? 0,
            'score' => $this->points
        ];
    }
}
