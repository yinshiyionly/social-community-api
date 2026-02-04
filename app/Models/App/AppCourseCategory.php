<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * 课程分类模型
 */
class AppCourseCategory extends Model
{
    use HasFactory;

    protected $table = 'app_course_category';
    protected $primaryKey = 'category_id';

    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    // 状态常量
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 2;

    // 删除标志
    const DEL_FLAG_NORMAL = 0;
    const DEL_FLAG_DELETED = 1;

    protected $fillable = [
        'parent_id',
        'category_name',
        'category_code',
        'icon',
        'cover',
        'description',
        'sort_order',
        'status',
        'create_by',
        'update_by',
        'del_flag',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'parent_id' => 'integer',
        'sort_order' => 'integer',
        'status' => 'integer',
        'del_flag' => 'integer',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    /**
     * 模型启动时添加全局作用域
     */
    protected static function booted()
    {
        static::addGlobalScope('not_deleted', function (Builder $builder) {
            $builder->where('del_flag', self::DEL_FLAG_NORMAL);
        });
    }

    /**
     * 父分类
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id', 'category_id');
    }

    /**
     * 子分类
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id', 'category_id');
    }

    /**
     * 查询作用域：启用状态
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', self::STATUS_ENABLED);
    }

    /**
     * 查询作用域：顶级分类
     */
    public function scopeTopLevel($query)
    {
        return $query->where('parent_id', 0);
    }
}
