<?php

namespace App\Http\Resources\App\Member;

use App\Services\App\FollowService;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 关注列表资源类
 */
class MemberFollowingListResource extends JsonResource
{
    /**
     * 转换资源为数组
     *
     * @param \Illuminate\Http\Request $request
     * @return array|null
     */
    public function toArray($request)
    {
        $member = $this->followMember;

        if (!$member) {
            return null;
        }

        return [
            'memberId' => $member->member_id,
            'nickname' => $member->nickname ?? '',
            'avatar' => $member->avatar ?? '',
            'bio' => $member->bio ?? '',
            'fansCount' => app(FollowService::class)->formatFansCount($member->fans_count ?? 0),
            'isFollowed' => true, // 关注列表中的用户必定已被关注
        ];
    }
}
