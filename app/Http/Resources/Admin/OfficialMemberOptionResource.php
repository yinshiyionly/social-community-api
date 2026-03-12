<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 后台帖子发帖人下拉资源。
 *
 * 字段约定：
 * - 返回 camelCase，直接对齐管理端下拉组件字段读取；
 * - 仅输出发帖选择必需字段，避免下拉接口返回冗余隐私信息。
 */
class OfficialMemberOptionResource extends JsonResource
{
    /**
     * 输出官方账号下拉选项。
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'memberId' => (int) $this->member_id,
            'nickname' => (string) ($this->nickname ?? ''),
            'avatar' => (string) ($this->avatar ?? ''),
            'officialLabel' => (string) ($this->official_label ?? ''),
        ];
    }
}
