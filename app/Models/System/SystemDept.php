<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;

class SystemDept extends Model
{
    protected $table = 'sys_dept';
    protected $primaryKey = 'dept_id';
    public $timestamps = false;

    protected $fillable = [
        'parent_id', 'ancestors', 'dept_name', 'order_num',
        'leader', 'phone', 'email', 'status', 'del_flag',
        'create_by', 'create_time', 'update_by', 'update_time'
    ];

    protected $casts = [
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    // 关联用户
    public function users()
    {
        return $this->hasMany(SystemUser::class, 'dept_id', 'dept_id');
    }

    // 关联角色（数据权限）
    public function roles()
    {
        return $this->belongsToMany(SystemRole::class, 'sys_role_dept', 'dept_id', 'role_id');
    }

    // 父部门
    public function parent()
    {
        return $this->belongsTo(SystemDept::class, 'parent_id', 'dept_id');
    }

    // 子部门
    public function children()
    {
        return $this->hasMany(SystemDept::class, 'parent_id', 'dept_id')->orderBy('order_num');
    }

    // 递归获取所有子部门
    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    // 构建部门树
    public static function buildDeptTree($depts, $parentId = 0)
    {
        $tree = [];
        foreach ($depts as $dept) {
            if ($dept->parent_id == $parentId) {
                $children = self::buildDeptTree($depts, $dept->dept_id);
                if (!empty($children)) {
                    $dept->children = $children;
                }
                $tree[] = $dept;
            }
        }
        return $tree;
    }

    // 获取祖先部门ID数组
    public function getAncestorIds()
    {
        return array_filter(explode(',', $this->ancestors));
    }

    // 更新祖先列表
    public function updateAncestors()
    {
        if ($this->parent_id == 0) {
            $this->ancestors = '0';
        } else {
            $parent = self::find($this->parent_id);
            $this->ancestors = $parent->ancestors . ',' . $this->parent_id;
        }
        $this->save();

        // 更新所有子部门的祖先列表
        $this->updateChildrenAncestors();
    }

    // 递归更新子部门祖先列表
    private function updateChildrenAncestors()
    {
        $children = $this->children;
        foreach ($children as $child) {
            $child->ancestors = $this->ancestors . ',' . $this->dept_id;
            $child->save();
            $child->updateChildrenAncestors();
        }
    }
}
