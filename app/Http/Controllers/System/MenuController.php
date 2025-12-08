<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\System\MenuResource;
use App\Models\System\SystemRole;
use App\Models\System\SystemMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller
{
    /**
     * 获取菜单列表
     */
    public function list(Request $request)
    {
        $query = SystemMenu::query();

        // 搜索条件
        if ($request->filled('menuName')) {
            $query->where('menu_name', 'like', '%' . $request->menuName . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $menus = $query->orderBy('parent_id')
                      ->orderBy('order_num')
                      ->get();

        // 构建菜单树
        $menuTree = SystemMenu::buildMenuTree($menus);

        return ApiResponse::success(['data' =>MenuResource::collection($menuTree)->resolve()], '查询成功');
    }

    /**
     * 获取菜单详情
     */
    public function show($menuId)
    {
        $menu = SystemMenu::find($menuId);

        if (!$menu) {
            return ApiResponse::error('菜单不存在');
        }

        return ApiResponse::success(['data' => new MenuResource($menu)], '查询成功');
    }

    /**
     * 新增菜单
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'menuName' => 'required|string|max:50',
            'parentId' => 'required|integer',
            'orderNum' => 'required|integer',
            'path' => 'nullable|string|max:200',
            'component' => 'nullable|string|max:255',
            'query' => 'nullable|string|max:255',
            'isFrame' => 'required|in:0,1',
            'isCache' => 'required|in:0,1',
            'menuType' => 'required|in:M,C,F',
            'visible' => 'required|in:0,1',
            'status' => 'required|in:0,1',
            'perms' => 'nullable|string|max:100',
            'icon' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }
        $menu = SystemMenu::create( [
            'menu_name' => $request->get('menuName'),
            'parent_id' => $request->get('parentId'),
            'order_num' => $request->get('orderNum'),
            'path' => $request->get('path') ?? '',
            'component' => $request->get('component'),
            'query' => $request->get('query'),
            'is_frame' => $request->get('isFrame'),
            'is_cache' => $request->get('isCache'),
            'menu_type' => $request->get('menuType'),
            'visible' => $request->get('visible'),
            'status' => $request->get('status'),
            'perms' => $request->get('perms'),
            'icon' => $request->get('icon') ?? '#',
            'create_by' => $request->user()->user_name,
            'create_time' => now(),
            'remark' => $request->get('remark') ?? ''
        ]);
        return ApiResponse::success([], '新增成功');
    }

    /**
     * 更新菜单
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'menuId' => 'required|exists:sys_menu,menu_id',
            'menuName' => 'required|string|max:50',
            'parentId' => 'required|integer',
            'orderNum' => 'required|integer',
            'path' => 'nullable|string|max:200',
            'component' => 'nullable|string|max:255',
            'query' => 'nullable|string|max:255',
            'isFrame' => 'required|in:0,1',
            'isCache' => 'required|in:0,1',
            'menuType' => 'required|in:M,C,F',
            'visible' => 'required|in:0,1',
            'status' => 'required|in:0,1',
            'perms' => 'nullable|string|max:100',
            'icon' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }

        $menu = SystemMenu::find($request->menuId);

        // 检查是否将父菜单设置为自己或子菜单
        if ($request->parentId == $request->menuId) {
            return ApiResponse::error('上级菜单不能选择自己');
        }

        $menu->update([
            'menu_name' => $request->get('menuName'),
            'parent_id' => $request->get('parentId'),
            'order_num' => $request->get('orderNum'),
            'path' => $request->get('path') ?? '',
            'component' => $request->get('component'),
            'query' => $request->get('query'),
            'is_frame' => $request->get('isFrame'),
            'is_cache' => $request->get('isCache'),
            'menu_type' => $request->get('menuType'),
            'visible' => $request->get('visible'),
            'status' => $request->get('status'),
            'perms' => $request->get('perms'),
            'icon' => $request->get('icon') ?? '#',
            'update_by' => $request->user()->user_name,
            'update_time' => now(),
            'remark' => $request->get('remark') ?? ''
        ]);

        return ApiResponse::success([], '修改成功');
    }

    /**
     * 删除菜单
     */
    public function destroy($menuId)
    {
        $menu = SystemMenu::find($menuId);

        if (!$menu) {
            return ApiResponse::error('菜单不存在');
        }

        // 检查是否存在子菜单
        $childCount = SystemMenu::where('parent_id', $menuId)->count();
        if ($childCount > 0) {
            return ApiResponse::error('存在子菜单，不允许删除');
        }

        // 检查菜单是否已分配给角色
        $roleCount = $menu->roles()->count();
        if ($roleCount > 0) {
            return ApiResponse::error('菜单已分配，不允许删除');
        }

        $menu->delete();

        return ApiResponse::success([], '删除成功');
    }

    /**
     * 获取菜单下拉树结构
     */
    public function treeselect()
    {
        $menus = SystemMenu::where('status', '0')
                        ->orderBy('parent_id')
                        ->orderBy('order_num')
                        ->get();

        // 构建菜单树
        $menuTree = $this->buildMenuTreeSelect($menus);

        return ApiResponse::success(['data' => $menuTree], '查询成功');
    }

    /**
     * 根据角色ID查询菜单下拉树结构
     */
    public function roleMenuTreeselect($roleId)
    {
        $menus = SystemMenu::where('status', '0')
                        ->orderBy('parent_id')
                        ->orderBy('order_num')
                        ->get();

        // 构建菜单树
        $menuTree = $this->buildMenuTreeSelect($menus);

        // 获取角色已分配的菜单
        $role = SystemRole::find($roleId);
        $checkedKeys = $role ? $role->getMenuIds() : [];

        return ApiResponse::success([
            'menus' => $menuTree,
            'checkedKeys' => $checkedKeys
        ], '查询成功');
    }

    /**
     * 构建菜单树选择结构
     */
    private function buildMenuTreeSelect($menus, $parentId = 0)
    {
        $tree = [];
        foreach ($menus as $menu) {
            if ($menu->parent_id == $parentId) {
                $node = [
                    'id' => $menu->menu_id,
                    'label' => $menu->menu_name,
                    'children' => []
                ];

                $children = $this->buildMenuTreeSelect($menus, $menu->menu_id);
                if (!empty($children)) {
                    $node['children'] = $children;
                }

                $tree[] = $node;
            }
        }
        return $tree;
    }
}
