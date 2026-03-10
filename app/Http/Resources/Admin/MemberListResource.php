<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 后台会员列表资源。
 */
class MemberListResource extends JsonResource
{
    /**
     * 输出后台会员列表项。
     *
     * 字段约定：
     * 1. 字段命名使用 camelCase，适配管理端前端约定；
     * 2. 统计字段缺失时回退为 0，避免前端因 null 值出现渲染异常；
     * 3. createdAt 固定输出 Y-m-d H:i:s，便于后台表格统一展示。
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'memberId' => (int) $this->member_id,
            'phone' => (string) ($this->phone ?? ''),
            'nickname' => (string) ($this->nickname ?? ''),
            'avatar' => (string) ($this->avatar ?? ''),
            'gender' => (int) ($this->gender ?? 0),
            'status' => (int) ($this->status ?? 1),
            'isOfficial' => (int) ($this->is_official ?? 0),
            'officialLabel' => (string) ($this->official_label ?? ''),
            'fansCount' => (int) ($this->fans_count ?? 0),
            'followingCount' => (int) ($this->following_count ?? 0),
            'likeCount' => (int) ($this->like_count ?? 0),
            'creationCount' => (int) ($this->creation_count ?? 0),
            'favoriteCount' => (int) ($this->favorite_count ?? 0),
            'createdAt' => $this->formatDateTime($this->created_at),
        ];
    }

    /**
     * 格式化时间字段为统一字符串。
     *
     * @param mixed $value
     * @return string|null
     */
    protected function formatDateTime($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        $timestamp = strtotime((string) $value);

        return $timestamp === false ? (string) $value : date('Y-m-d H:i:s', $timestamp);
    }
}

