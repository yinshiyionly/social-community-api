<?php

namespace App\Models\App;

use App\Models\Traits\HasOperator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 课程分类模型
 */
class AppCourseCategory extends Model
{
    use HasFactory, SoftDeletes, HasOperator;

    protected $table = 'app_course_category';
    protected $primaryKey = 'category_id';

    // 状态常量
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 2;

    protected $fillable = [
        'parent_id',
        'category_name',
        'category_code',
        'icon',
        'cover',
        'description',
        'sort_order',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'parent_id' => 'integer',
        'sort_order' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

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
