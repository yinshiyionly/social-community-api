<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Auth;

/**
 * 自动记录操作人审计字段（用户ID）
 *
 * 使用方式：在 Model 中 use HasOperator;
 *
 * 支持的字段：
 * - created_by: 创建时自动填充当前用户ID
 * - updated_by: 更新时自动填充当前用户ID
 * - deleted_by: 软删除时自动填充当前用户ID
 */
trait HasOperator
{
    /**
     * 启动 trait
     */
    public static function bootHasOperator(): void
    {
        // 创建时自动填充 created_by
        static::creating(function ($model) {
            if ($model->hasColumn('created_by') && empty($model->created_by)) {
                $model->created_by = static::getCurrentOperatorId();
            }
        });

        // 更新时自动填充 updated_by
        static::updating(function ($model) {
            if ($model->hasColumn('updated_by')) {
                $model->updated_by = static::getCurrentOperatorId();
            }
        });

        // 软删除时自动填充 deleted_by
        if (method_exists(static::class, 'bootSoftDeletes')) {
            static::deleting(function ($model) {
                if ($model->hasColumn('deleted_by') && !$model->isForceDeleting()) {
                    $model->deleted_by = static::getCurrentOperatorId();
                    $model->saveQuietly();
                }
            });
        }
    }

    /**
     * 获取当前操作人ID
     */
    protected static function getCurrentOperatorId(): ?int
    {
        return 0;
        // 从 Request 属性获取（JWT 中间件注入）
        $request = request();

        // System 端用户（后台管理员）
        if ($request && $request->attributes->has('system_user_id')) {
            return (int)$request->attributes->get('system_user_id');
        }

        // App 端用户（会员）
        if ($request && $request->attributes->has('member_id')) {
            return (int)$request->attributes->get('member_id');
        }

        // Admin guard 登录用户
        if (Auth::guard('admin')->check()) {
            return (int)Auth::guard('admin')->id();
        }

        // 默认 Laravel Auth
        if (Auth::check()) {
            return (int)Auth::id();
        }

        return null;
    }

    /**
     * 检查模型是否有指定字段
     */
    protected function hasColumn(string $column): bool
    {
        return in_array($column, $this->getFillable())
            || $this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), $column);
    }

    /**
     * 手动设置操作人ID（用于队列任务等无登录态场景）
     */
    public function setOperatorId(int $operatorId): self
    {
        if ($this->exists) {
            $this->updated_by = $operatorId;
        } else {
            $this->created_by = $operatorId;
        }
        return $this;
    }
}
