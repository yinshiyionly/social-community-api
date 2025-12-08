<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\System\DeptResource;
use App\Http\Resources\System\RoleResource;
use App\Models\System\SystemRole;
use App\Models\System\SystemDept;
use App\Models\System\SystemUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    /**
     * 获取角色列表
     */
    public function list(Request $request)
    {
        $query = SystemRole::where('del_flag', '0');

        // 搜索条件
        if ($request->filled('roleName')) {
            $query->where('role_name', 'like', '%' . $request->roleName . '%');
        }

        /*if ($request->filled('roleKey')) {
            $query->where('role_key', 'like', '%' . $request->roleKey . '%');
        }*/

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $pageNum = $request->get('pageNum', 1);
        $pageSize = $request->get('pageSize', 10);

        $roles = $query->orderBy('role_sort')->paginate($pageSize, ['*'], 'page', $pageNum);

        return ApiResponse::paginate($roles, RoleResource::class, '查询成功');
    }

    /**
     * 获取角色详情
     */
    public function show($roleId)
    {
        $role = SystemRole::with(['menus', 'depts'])
            ->where('role_id', $roleId)
            ->where('del_flag', '0')
            ->first();

        if (!$role) {
            return ApiResponse::error('角色不存在');
        }

        $role->menuIds = $role->getMenuIds();
        $role->deptIds = $role->getDeptIds();

        return ApiResponse::success(['data' => new RoleResource($role)], '查询成功');
    }

    /**
     * 新增角色
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'roleName' => 'required|string|max:30',
            // 'roleKey' => 'required|string|max:100|unique:sys_role,role_key',
            'roleSort' => 'required|integer',
            'status' => 'required|in:0,1',
            'menuIds' => 'nullable|array',
            'deptIds' => 'nullable|array',
            // 'dataScope' => 'required|in:1,2,3,4,5',
            'menuCheckStrictly' => 'nullable|boolean',
            'deptCheckStrictly' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }

        $role = SystemRole::create([
            'role_name' => $request->roleName,
            // 前端页面已移除权限字符输入框，所有保存为统一的默认字符-common
            'role_key' => 'common',
            'role_sort' => $request->roleSort,
            // 默认全部数据权限
            'data_scope' => $request->dataScope ?? 1,
            'menu_check_strictly' => $request->menuCheckStrictly ? '1' : '0',
            'dept_check_strictly' => $request->deptCheckStrictly ? '1' : '0',
            'status' => $request->status,
            'create_by' => $request->user()->user_name,
            'create_time' => now(),
            'remark' => $request->remark
        ]);

        // 分配菜单权限
        if ($request->filled('menuIds')) {
            $role->menus()->sync($request->menuIds);
        }

        // 分配数据权限
        if ($request->dataScope == '2' && $request->filled('deptIds')) {
            $role->depts()->sync($request->deptIds);
        }

        return ApiResponse::success([], '新增成功');
    }

    /**
     * 更新角色
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'roleId' => 'required|exists:sys_role,role_id',
            'roleName' => 'required|string|max:30',
            // 'roleKey' => 'required|string|max:100|unique:sys_role,role_key,' . $request->roleId . ',role_id',
            'roleSort' => 'required|integer',
            'status' => 'required|in:0,1',
            'menuIds' => 'nullable|array',
            'deptIds' => 'nullable|array',
            'dataScope' => 'required|in:1,2,3,4,5',
            'menuCheckStrictly' => 'nullable|boolean',
            'deptCheckStrictly' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }

        $role = SystemRole::find($request->roleId);

        $role->update([
            'role_name' => $request->roleName,
            // 前端页面已移除权限字符输入框，所有保存为统一的默认字符-common
            'role_key' => 'common',
            'role_sort' => $request->roleSort,
            'data_scope' => $request->dataScope,
            'menu_check_strictly' => $request->menuCheckStrictly ? '1' : '0',
            'dept_check_strictly' => $request->deptCheckStrictly ? '1' : '0',
            'status' => $request->status,
            'update_by' => $request->user()->user_name,
            'update_time' => now(),
            'remark' => $request->remark
        ]);

        // 更新菜单权限
        if ($request->filled('menuIds')) {
            $role->menus()->sync($request->menuIds);
        }

        // 更新数据权限
        if ($request->dataScope == '2' && $request->filled('deptIds')) {
            $role->depts()->sync($request->deptIds);
        } else {
            $role->depts()->detach();
        }

        return ApiResponse::success([], '修改成功');
    }

    /**
     * 删除角色
     */
    public function destroy($roleId)
    {
        $role = SystemRole::find($roleId);

        if (!$role) {
            return ApiResponse::error('角色不存在');
        }

        // 检查是否有用户使用该角色
        $userCount = $role->users()->count();
        if ($userCount > 0) {
            return ApiResponse::error('该角色下还有用户，不能删除');
        }

        $role->update([
            'del_flag' => '2',
            'update_by' => request()->user()->user_name,
            'update_time' => now()
        ]);

        return ApiResponse::success([], '删除成功');
    }

    /**
     * 修改角色状态
     */
    public function changeStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'roleId' => 'required|exists:sys_role,role_id',
            'status' => 'required|in:0,1'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }

        $role = SystemRole::find($request->roleId);

        $role->update([
            'status' => $request->status,
            'update_by' => $request->user()->user_name,
            'update_time' => now()
        ]);

        return ApiResponse::success([], '修改成功');
    }

    /**
     * 数据权限设置
     */
    public function dataScope(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'roleId' => 'required|exists:sys_role,role_id',
            'dataScope' => 'required|in:1,2,3,4,5',
            'deptIds' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }

        $role = SystemRole::find($request->roleId);

        $role->update([
            'data_scope' => $request->dataScope,
            'update_by' => $request->user()->user_name,
            'update_time' => now()
        ]);

        // 更新数据权限
        if ($request->dataScope == '2' && $request->filled('deptIds')) {
            $role->depts()->sync($request->deptIds);
        } else {
            $role->depts()->detach();
        }

        return ApiResponse::success([], '设置成功');
    }

    /**
     * 根据角色ID查询部门树结构
     */
    public function deptTree($roleId)
    {
        $depts = SystemDept::where('status', '0')
            ->where('del_flag', '0')
            ->orderBy('parent_id')
            ->orderBy('order_num')
            ->get();

        $tree = SystemDept::buildDeptTree($depts);

        $role = SystemRole::find($roleId);
        $checkedKeys = $role ? $role->getDeptIds() : [];

        return ApiResponse::success([
            'depts' => DeptResource::collection($tree)->resolve(),
            'checkedKeys' => $checkedKeys
        ], '设置成功');
    }

    /**
     * 查询角色已授权用户列表
     */
    public function allocatedUserList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'roleId' => 'required|exists:sys_role,role_id'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }

        $roleId = $request->roleId;
        $query = SystemUser::with(['dept'])
            ->whereHas('roles', function ($q) use ($roleId) {
                $q->where('sys_role.role_id', $roleId);
            })
            ->where('sys_user.del_flag', '0');

        // 搜索条件
        if ($request->filled('userName')) {
            $query->where('sys_user.user_name', 'like', '%' . $request->userName . '%');
        }

        if ($request->filled('phonenumber')) {
            $query->where('sys_user.phonenumber', 'like', '%' . $request->phonenumber . '%');
        }

        $pageNum = $request->get('pageNum', 1);
        $pageSize = $request->get('pageSize', 10);

        $users = $query->paginate($pageSize, ['*'], 'page', $pageNum);

        return ApiResponse::paginate($users, \App\Http\Resources\System\UserResource::class, '查询成功');
    }

    /**
     * 查询角色未授权用户列表
     */
    public function unallocatedUserList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'roleId' => 'required|exists:sys_role,role_id'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }

        $roleId = $request->roleId;
        $query = SystemUser::with(['dept'])
            ->whereDoesntHave('roles', function ($q) use ($roleId) {
                $q->where('sys_role.role_id', $roleId);
            })
            ->where('sys_user.del_flag', '0');

        // 搜索条件
        if ($request->filled('userName')) {
            $query->where('sys_user.user_name', 'like', '%' . $request->userName . '%');
        }

        if ($request->filled('phonenumber')) {
            $query->where('sys_user.phonenumber', 'like', '%' . $request->phonenumber . '%');
        }

        $pageNum = $request->get('pageNum', 1);
        $pageSize = $request->get('pageSize', 10);

        $users = $query->paginate($pageSize, ['*'], 'page', $pageNum);

        return ApiResponse::paginate($users, \App\Http\Resources\System\UserResource::class, '查询成功');
    }

    /**
     * 取消用户授权角色
     */
    public function authUserCancel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'roleId' => 'required|exists:sys_role,role_id',
            'userId' => 'required|exists:sys_user,user_id'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }

        $role = SystemRole::find($request->roleId);
        $user = SystemUser::find($request->userId);

        // 检查是否为超级管理员
        if ($user->user_id == 1) {
            return ApiResponse::error('不能取消超级管理员的角色授权');
        }

        // 取消角色授权
        $role->users()->detach($request->userId);

        return ApiResponse::success([], '取消授权成功');
    }

    /**
     * 批量取消用户授权角色
     */
    public function authUserCancelAll(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'roleId' => 'required|exists:sys_role,role_id',
            'userIds' => 'required|string'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }

        $roleId = $request->roleId;
        $userIds = explode(',', $request->userIds);

        // 检查是否包含超级管理员
        if (in_array('1', $userIds)) {
            return ApiResponse::error('不能取消超级管理员的角色授权');
        }

        $role = SystemRole::find($roleId);

        // 批量取消角色授权
        $role->users()->detach($userIds);

        return ApiResponse::success([], '批量取消授权成功');
    }

    /**
     * 授权用户选择
     */
    public function authUserSelectAll(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'roleId' => 'required|exists:sys_role,role_id',
            'userIds' => 'required|string'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }

        $roleId = $request->roleId;
        $userIds = explode(',', $request->userIds);

        // 验证用户是否存在
        $existingUserIds = SystemUser::whereIn('user_id', $userIds)
            ->where('del_flag', '0')
            ->pluck('user_id')
            ->toArray();

        if (count($existingUserIds) != count($userIds)) {
            return ApiResponse::error('部分用户不存在或已被删除');
        }

        $role = SystemRole::find($roleId);

        // 批量授权用户
        $role->users()->syncWithoutDetaching($existingUserIds);

        return ApiResponse::success([], '批量授权成功');
    }
}
