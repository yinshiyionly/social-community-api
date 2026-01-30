<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppCourseCategory extends Model
{
    use HasFactory;

    protected $table = 'app_course_category';
    protected $primaryKey = 'category_id';
    public $timestamps = false;

    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 2;

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
        'create_time',
        'update_by',
        'update_time',
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
     * 关联课程
     */
    public function courses()
    {
        return $this->hasMany(AppCourseBase::class, 'category_id', 'category_id');
    }

    /**
     * 关联父分类
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id', 'category_id');
    }

    /**
     * 关联子分类
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
        return $query->where('status', self::STATUS_ENABLED)
                     ->where('del_flag', self::DEL_FLAG_NORMAL);
    }

    /**
     * 查询作用域：顶级分类
     */
    public function scopeTopLevel($query)
    {
        return $query->where('parent_id', 0);
    }

    /**
     * 获取分类树
     */
    public static function getTree()
    {
        return self::enabled()
            ->topLevel()
            ->with(['children' => function ($query) {
                $query->enabled()->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();
    }
}
