<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App模块配置模型。
 *
 * 职责：
 * 1. 管理 app_config 表中的运行时配置；
 * 2. 提供按 config_key/env/platform 的查询能力；
 * 3. 通过 JSONB 字段承载配置值与显隐时间规则。
 */
class AppConfig extends Model
{
    use HasFactory;

    protected $table = 'app_config';

    protected $primaryKey = 'config_id';

    protected $fillable = [
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
        'description',
    ];

    protected $casts = [
        'config_id' => 'integer',
        'config_value' => 'array',
        'visibility_rule' => 'array',
        'is_enabled' => 'boolean',
        'sort_num' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // 配置值类型常量
    const TYPE_BOOL = 'bool';
    const TYPE_NUMBER = 'number';
    const TYPE_STRING = 'string';
    const TYPE_JSON = 'json';
    const TYPE_ARRAY = 'array';

    // 显隐模式常量
    const VISIBILITY_MODE_ALWAYS = 'always';
    const VISIBILITY_MODE_WINDOW = 'window';

    /**
     * 查询作用域：仅返回启用配置。
     *
     * 语义：
     * - is_enabled=true 表示配置可参与运行时读取；
     * - 具体是否在时间窗内生效由业务层解析 visibility_rule。
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    /**
     * 查询作用域：按配置键筛选。
     *
     * @param Builder $query
     * @param string $configKey
     * @return Builder
     */
    public function scopeByConfigKey(Builder $query, string $configKey): Builder
    {
        return $query->where('config_key', $configKey);
    }

    /**
     * 查询作用域：按环境筛选。
     *
     * @param Builder $query
     * @param string $env
     * @return Builder
     */
    public function scopeByEnv(Builder $query, string $env): Builder
    {
        return $query->where('env', $env);
    }

    /**
     * 查询作用域：按平台筛选。
     *
     * @param Builder $query
     * @param string $platform
     * @return Builder
     */
    public function scopeByPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }
}
