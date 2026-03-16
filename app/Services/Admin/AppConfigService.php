<?php

namespace App\Services\Admin;

use App\Models\App\AppConfig;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * App 配置管理服务。
 *
 * 职责：
 * 1. 封装 app_config 的增删改查逻辑；
 * 2. 统一处理 Admin 入参到数据库字段映射；
 * 3. 组装 visibility_rule 结构，保证落库格式一致。
 */
class AppConfigService
{
    /**
     * 获取配置列表（分页）。
     *
     * 过滤规则：
     * - 仅在筛选值存在时拼接 where 条件；
     * - isEnabled 支持 0/1/true/false 字符串与布尔混合输入；
     * - 按 sort_num DESC, config_id DESC 保持管理端稳定排序。
     *
     * @param array<string, mixed> $filters
     * @param int $pageNum
     * @param int $pageSize
     * @return LengthAwarePaginator
     */
    public function getList(array $filters, int $pageNum = 1, int $pageSize = 10): LengthAwarePaginator
    {
        $query = AppConfig::query()->select([
            'config_id',
            'config_key',
            'config_name',
            'config_type',
            'group_name',
            'config_value',
            'visibility_rule',
            'is_enabled',
            'sort_num',
            'env',
            'platform',
            'created_at',
        ]);

        if (!empty($filters['configKey'])) {
            $query->where('config_key', 'like', '%' . $filters['configKey'] . '%');
        }

        if (!empty($filters['groupName'])) {
            $query->where('group_name', 'like', '%' . $filters['groupName'] . '%');
        }

        if (!empty($filters['env'])) {
            $query->where('env', $filters['env']);
        }

        if (!empty($filters['platform'])) {
            $query->where('platform', $filters['platform']);
        }

        $isEnabled = $this->parseNullableBool($filters['isEnabled'] ?? null);
        if ($isEnabled !== null) {
            $query->where('is_enabled', $isEnabled);
        }

        $query->orderByDesc('sort_num')
            ->orderByDesc('config_id');

        return $query->paginate($pageSize, ['*'], 'pageNum', $pageNum);
    }

    /**
     * 获取配置详情。
     *
     * @param int $configId
     * @return AppConfig|null
     */
    public function getDetail(int $configId): ?AppConfig
    {
        return AppConfig::query()
            ->where('config_id', $configId)
            ->first();
    }

    /**
     * 创建配置。
     *
     * @param array<string, mixed> $data
     * @return AppConfig
     */
    public function create(array $data): AppConfig
    {
        return AppConfig::create($this->buildWriteData($data));
    }

    /**
     * 更新配置（全量字段更新）。
     *
     * @param int $configId
     * @param array<string, mixed> $data
     * @return bool
     */
    public function update(int $configId, array $data): bool
    {
        $config = AppConfig::query()->where('config_id', $configId)->first();

        if (!$config) {
            return false;
        }

        return $config->update($this->buildWriteData($data));
    }

    /**
     * 修改启用状态。
     *
     * @param int $configId
     * @param bool $isEnabled
     * @return bool
     */
    public function changeStatus(int $configId, bool $isEnabled): bool
    {
        return AppConfig::query()
            ->where('config_id', $configId)
            ->update(['is_enabled' => $isEnabled]) > 0;
    }

    /**
     * 删除配置（物理删除）。
     *
     * @param int $configId
     * @return bool
     */
    public function delete(int $configId): bool
    {
        return AppConfig::query()
            ->where('config_id', $configId)
            ->delete() > 0;
    }

    /**
     * 构建落库数据结构。
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function buildWriteData(array $data): array
    {
        return [
            'config_key' => $data['configKey'],
            'config_name' => $data['configName'],
            'config_type' => $data['configType'],
            'group_name' => $data['groupName'] ?? 'default',
            'config_value' => $data['configValue'],
            'visibility_rule' => $this->buildVisibilityRule($data),
            'is_enabled' => isset($data['isEnabled']) ? (bool) $data['isEnabled'] : true,
            'sort_num' => isset($data['sortNum']) ? (int) $data['sortNum'] : 0,
            'env' => $data['env'],
            'platform' => $data['platform'],
            'description' => $data['description'] ?? '',
        ];
    }

    /**
     * 组装时间显隐规则 JSON 结构。
     *
     * @param array<string, mixed> $data
     * @return array{mode:string, timezone:string, windows:array<int, array{startAt:string, endAt:string, visible:bool}>}
     */
    protected function buildVisibilityRule(array $data): array
    {
        $rawWindows = $data['windows'] ?? [];
        $windows = [];

        foreach ($rawWindows as $window) {
            if (!is_array($window)) {
                continue;
            }

            $windows[] = [
                'startAt' => (string) ($window['startAt'] ?? ''),
                'endAt' => (string) ($window['endAt'] ?? ''),
                'visible' => (bool) ($window['visible'] ?? true),
            ];
        }

        return [
            'mode' => (string) $data['visibilityMode'],
            'timezone' => (string) $data['timezone'],
            'windows' => $windows,
        ];
    }

    /**
     * 解析可空布尔筛选值。
     *
     * @param mixed $value
     * @return bool|null
     */
    protected function parseNullableBool($value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            if ($value === 1) {
                return true;
            }
            if ($value === 0) {
                return false;
            }

            return null;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }

            if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
        }

        return null;
    }
}
