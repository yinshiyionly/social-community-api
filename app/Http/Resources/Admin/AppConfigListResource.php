<?php

namespace App\Http\Resources\Admin;

use App\Models\App\AppConfig;
use Illuminate\Http\Resources\Json\JsonResource;

class AppConfigListResource extends JsonResource
{
    /**
     * 输出 App 配置列表项。
     *
     * 字段约定：
     * - 返回字段统一 camelCase，兼容 Admin 端字段读取习惯；
     * - visibilityMode/timezone/windows 从 visibility_rule 拆解得到；
     * - createdAt 为展示字段，统一格式 `Y-m-d H:i:s`。
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
            'isEnabled' => (bool) $this->is_enabled,
            'sortNum' => (int) $this->sort_num,
            'env' => $this->env,
            'platform' => $this->platform,
            'createdAt' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
