<?php

namespace App\Http\Resources\App\Member;

use App\Services\App\FollowService;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 粉丝列表资源类
 */
class MemberFansListResource extends JsonResource
{
    /**
     * 转换资源为数组
     *
     * @param \Illuminate\Http\Request $request
     * @return array|null
     */
    public function toArray($request)
    {
        $member = $this->member;

        if (!$member) {
            return null;
        }

        return [
            'id' => $member->member_id,
            'nickname' => $member->nickname ?? '',
            'avatar' => $member->avatar ?? '',
            'bio' => $member->bio ?? '',
            'fansCount' => app(FollowService::class)->formatFansCount($member->fans_count ?? 0),
            'isFollowed' => $this->is_followed ?? false,
        ];
    }
}
