<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Auth;

/**
 * 自动记录操作人审计字段
 *
 * 使用方式：在 Model 中 use HasOperator;
 *
 * 支持的字段：
 * - created_by: 创建时自动填充当前用户
 * - updated_by: 更新时自动填充当前用户
 * - deleted_by: 软删除时自动填充当前用户
 *
 * 获取操作人的优先级
 * - Admin guard 登录用户
 * - Request 属性中的 system_user_id（System JWT 中间件注入）
 * - Request 属性中的 member_id（App JWT 中间件注入）
 * - 默认 Laravel Auth 用户
 *
 * 队列任务等无状态场景需要手动指定操作人
 * $course->setOperator('ID')->save()
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
                $model->created_by = static::getCurrentOperator();
            }
        });

        // 更新时自动填充 updated_by
        static::updating(function ($model) {
            if ($model->hasColumn('updated_by')) {
                $model->updated_by = static::getCurrentOperator();
            }
        });

        // 软删除时自动填充 deleted_by
        if (method_exists(static::class, 'bootSoftDeletes')) {
            static::deleting(function ($model) {
                if ($model->hasColumn('deleted_by') && !$model->isForceDeleting()) {
                    $model->deleted_by = static::getCurrentOperator();
                    $model->saveQuietly();
                }
            });
        }
    }

    /**
     * 获取当前操作人标识
     */
    protected static function getCurrentOperator(): ?string
    {
        // 优先从 Admin 端获取（后台管理）
        if (Auth::guard('admin')->check()) {
            $user = Auth::guard('admin')->user();
            return $user->user_name ?? (string)$user->getKey();
        }

        // 从 Request 属性获取（JWT 中间件注入）
        $request = request();

        // System 端用户
        if ($request->attributes->has('system_user_id')) {
            return (string)$request->attributes->get('system_user_id');
        }

        // App 端用户（member_id）
        if ($request->attributes->has('member_id')) {
            return (string)$request->attributes->get('member_id');
        }

        // 默认 Laravel Auth
        if (Auth::check()) {
            $user = Auth::user();
            return $user->user_name ?? $user->name ?? (string)$user->getKey();
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
     * 手动设置操作人（用于队列任务等无登录态场景）
     */
    public function setOperator(string $operator): self
    {
        if ($this->exists) {
            $this->updated_by = $operator;
        } else {
            $this->created_by = $operator;
        }
        return $this;
    }
}
