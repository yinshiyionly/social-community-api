<?php

namespace App\Http\Resources\App\Member;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 当前登录用户个人信息资源类
 */
class MemberInfoResource extends JsonResource
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
            'userId' => $this->member_id,
            'phone' => $this->phone ?? '',
            'nickname' => $this->nickname ?? '',
            'avatar' => $this->avatar ?? '',
            'gender' => $this->gender ?? 0,
            'birthday' => $this->birthday ? $this->birthday->format('Y-m-d') : null,
            'bio' => $this->bio ?? '',
            'level' => $this->level ?? 1,
            'points' => $this->points ?? 0,
            'coin' => $this->coin ?? 0,
            'fansCount' => $this->fans_count ?? 0,
            'followingCount' => $this->following_count ?? 0,
            'likeCount' => $this->like_count ?? 0,
        ];
    }

    /**
     * 手机号脱敏
     *
     * @param string|null $phone
     * @return string
     */
    protected function maskPhone($phone): string
    {
        if (empty($phone) || strlen($phone) < 7) {
            return '';
        }

        return substr($phone, 0, 3) . '****' . substr($phone, -4);
    }
}
