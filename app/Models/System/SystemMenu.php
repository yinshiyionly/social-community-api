<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class SystemMenu extends Model
{
    protected $table = 'sys_menu';
    protected $primaryKey = 'menu_id';
    public $timestamps = false;

    protected $fillable = [
        'menu_name', 'parent_id', 'order_num', 'path', 'component',
        'query', 'is_frame', 'is_cache', 'menu_type', 'visible',
        'status', 'perms', 'icon', 'create_by', 'create_time',
        'update_by', 'update_time', 'remark'
    ];

    protected $casts = [
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    // 关联角色
    public function roles()
    {
        return $this->belongsToMany(SystemRole::class, 'sys_role_menu', 'menu_id', 'role_id');
    }

    // 父菜单
    public function parent()
    {
        return $this->belongsTo(SystemMenu::class, 'parent_id', 'menu_id');
    }

    // 子菜单
    public function children()
    {
        return $this->hasMany(SystemMenu::class, 'parent_id', 'menu_id')->orderBy('order_num');
    }

    // 递归获取所有子菜单
    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    // 构建菜单树
    public static function buildMenuTree($menus, $parentId = 0)
    {
        $tree = [];
        foreach ($menus as $menu) {
            // 只处理目录(M)和菜单(C)类型，排除按钮(F)类型
            if ($menu['parent_id'] == $parentId && in_array($menu['menu_type'], ['M', 'C'])) {
                // 过滤子菜单，只获取目录(M)和菜单(C)类型，排除按钮(F)
//                $filteredMenus = $menus->filter(function($item) {
//                    return in_array($item->menu_type, ['M', 'C']);
//                });

                $children = self::buildMenuTree($menus, $menu['menu_id']);
                if (!empty($children)) {
                    $menu['children'] = $children;
                }
                $tree[] = $menu;
            }
        }
        return $tree;
    }

    // 转换为前端路由格式
    public function toRouterFormat()
    {
        $router = [
            'name' => ucfirst($this['path']), // 首字母大写，符合RuoYi规范
            'path' => $this->parent_id == 0 ? '/' . $this->path : $this->path,
            'hidden' => $this->visible == '1',
            'component' => $this->component ?: 'Layout',
            'meta' => [
                'title' => $this->menu_name,
                'icon' => $this->icon,
                'noCache' => $this->is_cache == '1',
                'link' => $this->is_frame == '0' ? $this->path : null
            ]
        ];

        // 如果是目录类型(M)且有子菜单，添加redirect和alwaysShow
        if ($this->menu_type == 'M') {
            $router['redirect'] = 'noRedirect';
            $router['alwaysShow'] = true;
        }

        if ($this->query) {
            $router['query'] = $this->query;
        }

        return $router;
    }
}
