<?php

namespace App\Http\Resources\Admin;

use App\Models\App\AppConfig;
use Illuminate\Http\Resources\Json\JsonResource;

class AppConfigResource extends JsonResource
{
    /**
     * 输出 App 配置详情。
     *
     * 字段约定：
     * - 保持 camelCase 返回，避免前端二次字段转换；
     * - 同时返回拆分字段与 visibilityRule 原始结构，便于编辑页回填和问题排查；
     * - createdAt/updatedAt 统一格式化为 `Y-m-d H:i:s`。
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $visibilityRule = is_array($this->visibility_rule) ? $this->visibility_rule : [];

        return [
            'configId' => $this->config_id,
            'configKey' => $this->config_key,
            'configName' => $this->config_name,
            'configType' => $this->config_type,
            'groupName' => $this->group_name,
            'configValue' => $this->config_value,
            'visibilityMode' => $visibilityRule['mode'] ?? AppConfig::VISIBILITY_MODE_ALWAYS,
            'timezone' => $visibilityRule['timezone'] ?? 'Asia/Shanghai',
            'windows' => isset($visibilityRule['windows']) && is_array($visibilityRule['windows'])
                ? array_values($visibilityRule['windows'])
                : [],
            'visibilityRule' => $visibilityRule,
            'isEnabled' => (bool) $this->is_enabled,
            'sortNum' => (int) $this->sort_num,
            'env' => $this->env,
            'platform' => $this->platform,
            'description' => $this->description,
            'createdAt' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updatedAt' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
