<?php

namespace App\Http\Resources\App;

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
            'memberId' => $member->member_id,
            'nickname' => $member->nickname ?? '',
            'avatar' => $member->avatar ?? '',
            'bio' => $member->bio ?? '',
        ];
    }
}
