<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 关注用户列表资源类 - 用于关注列表展示
 */
class FollowMemberListResource extends JsonResource
{
    /**
     * 转换资源为数组
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $followMember = $this->followMember;

        return [
            'id' => $followMember ? $followMember->member_id : null,
            'nickname' => $followMember ? ($followMember->nickname ?? '') : '',
            'avatar' => $followMember ? ($followMember->avatar ?? '') : ''
        ];
    }
}
