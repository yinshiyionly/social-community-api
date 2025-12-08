<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\System\DeptResource;
use App\Http\Resources\System\PostResource;
use App\Http\Resources\System\RoleResource;
use App\Http\Resources\System\UserResource;
use App\Models\System\SystemRole;
use App\Models\System\SystemDept;
use App\Models\System\SystemPost;
use App\Models\System\SystemUser;
use App\Models\TeamManagement\SaleMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * 获取用户列表
     */
    public function list(Request $request)
    {
        $query = SystemUser::with(['dept', 'roles'])
            ->where('sys_user.del_flag', '0');

        // 搜索条件
        if ($request->filled('userName')) {
            $query->where('sys_user.user_name', 'like', '%' . $request->userName . '%');
        }

        if ($request->filled('phonenumber')) {
            $query->where('sys_user.phonenumber', 'like', '%' . $request->phonenumber . '%');
        }

        if ($request->filled('status')) {
            $query->where('sys_user.status', $request->status);
        }

        if ($request->filled('deptId')) {
            $query->where('sys_user.dept_id', $request->deptId);
        }

        // 数据权限过滤
        $currentUser = $request->user();
        if (!$currentUser->isAdmin()) {
            $dataScope = $currentUser->getDataScope();
            switch ($dataScope) {
                case '2': // 自定数据权限
                    $deptIds = [];
                    foreach ($currentUser->roles as $role) {
                        $deptIds = array_merge($deptIds, $role->getDeptIds());
                    }
                    $query->whereIn('sys_user.dept_id', array_unique($deptIds));
                    break;
                case '3': // 本部门数据权限
                    $query->where('sys_user.dept_id', $currentUser->dept_id);
                    break;
                case '4': // 本部门及以下数据权限
                    $dept = SystemDept::find($currentUser->dept_id);
                    if ($dept) {
                        $deptIds = [$currentUser->dept_id];
                        $this->getChildDeptIds($dept, $deptIds);
                        $query->whereIn('sys_user.dept_id', $deptIds);
                    }
                    break;
                case '5': // 仅本人数据权限
                    $query->where('sys_user.user_id', $currentUser->user_id);
                    break;
            }
        }

        $pageNum = $request->get('pageNum', 1);
        $pageSize = $request->get('pageSize', 10);

        $users = $query->paginate($pageSize, ['*'], 'page', $pageNum);

        return ApiResponse::paginate($users, UserResource::class, '查询成功');
    }

    /**
     * todo 还不清楚这个接口的具体作用是什么
     * 对应 /api/system/user/ 路由 无参数
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function default()
    {
        // 获取角色和岗位选项
        $roles = SystemRole::where('status', '0')->where('del_flag', '0')->get();
        $posts = SystemPost::where('status', '0')->get();

        return ApiResponse::success([
            'roles' => RoleResource::collection($roles)->resolve(),
            'posts' => PostResource::collection($posts)->resolve()
        ], '查询成功');
    }

    /**
     * 获取当前用户个人信息
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        // 加载关联数据
        $user->load(['dept', 'roles', 'posts']);

        // 获取角色组和岗位组信息
        $roleGroup = $user->roles->pluck('role_name')->implode(',');
        $postGroup = $user->posts->pluck('post_name')->implode(',');

        // 构建返回数据，匹配若依的数据结构
        $data = new UserResource($user);
        $userData = $data->resolve();

        // 添加密码字段（加密后的）
        $userData['password'] = $user->password;

        return ApiResponse::success([
            'data' => $userData,
            'roleGroup' => $roleGroup ?: '',
            'postGroup' => $postGroup ?: ''
        ], '操作成功');
    }

    /**
     * 更新当前用户个人信息
     */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nickName' => 'required|string|max:30',
            'email' => 'nullable|email|max:50',
            'phonenumber' => 'nullable|string|max:11',
            'sex' => 'nullable|in:0,1,2'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }

        $user = $request->user();

        $user->update([
            'nick_name' => $request->nickName,
            'email' => $request->email,
            'phonenumber' => $request->phonenumber,
            'sex' => $request->sex ?? '0',
            'update_by' => $user->user_name,
            'update_time' => now()
        ]);

        return ApiResponse::success([], '修改成功');
    }

    /**
     * 修改当前用户密码
     */
    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'oldPassword' => 'required|string',
            'newPassword' => 'required|string|min:6|max:20'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }

        $user = $request->user();

        // 验证旧密码
        if (!Hash::check($request->oldPassword, $user->password)) {
            return ApiResponse::error('旧密码错误');
        }

        // 检查新密码是否与旧密码相同
        if (Hash::check($request->newPassword, $user->password)) {
            return ApiResponse::error('新密码不能与旧密码相同');
        }

        // 更新密码
        $user->update([
            'password' => Hash::make($request->newPassword),
            'pwd_update_date' => now(),
            'update_by' => $user->user_name,
            'update_time' => now()
        ]);

        return ApiResponse::success([], '修改成功');
    }

    /**
     * 修改用户头像
     */
    public function updateAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|string|url'
        ], [
            'avatar.required' => '头像地址不能为空',
            'avatar.string' => '头像地址必须是字符串',
            'avatar.url' => '头像地址格式不正确'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }

        $user = $request->user();

        $user->update([
            'avatar' => $request->avatar,
            'update_by' => $user->user_name,
            'update_time' => now()
        ]);

        return ApiResponse::success([], '修改成功');
    }

    /**
     * 获取用户详情
     */
    public function show($userId)
    {
        $user = SystemUser::with(['dept', 'roles', 'posts'])
            ->where('user_id', $userId)
            ->where('del_flag', '0')
            ->first();

        if (!$user) {
            return ApiResponse::error('用户不存在');
        }

        // 获取角色和岗位选项
        $roles = SystemRole::where('status', '0')->where('del_flag', '0')->get();
        $posts = SystemPost::where('status', '0')->get();

        $user->roleIds = $user->roles->pluck('role_id')->toArray();
        $user->postIds = $user->posts->pluck('post_id')->toArray();

        return ApiResponse::success([
            'data' => new UserResource($user),
            'roles' => RoleResource::collection($roles)->resolve(),
            'posts' => PostResource::collection($posts)->resolve(),
            'roleIds' => $user->roleIds,
            'postIds' => $user->postIds
        ], '查询成功');


        return response()->json([
            'code' => 200,
            'msg' => '查询成功',
            'data' => new UserResource($user),
            'roles' => RoleResource::collection($roles)->resolve(),
            'posts' => PostResource::collection($posts)->resolve(),
            'roleIds' => $user->roleIds,
            'postIds' => $user->postIds
        ]);
    }

    /**
     * 新增用户
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userName' => [
                'required',
                'string',
                'max:30',
                Rule::unique('sys_user', 'user_name')
                    ->where(function ($q) {
                        $q->where('del_flag', 0)
                            ->where('user_name', request()->get('userName', ''));
                    }),
            ],
            'nickName' => 'required|string|max:30',
            'password' => 'required|string|min:6',
            'email' => 'nullable|email|max:50',
            'phonenumber' => 'nullable|string|max:11',
            'sex' => 'nullable|in:0,1,2',
            'status' => 'required|in:0,1',
            'deptId' => 'nullable|exists:sys_dept,dept_id',
            'roleIds' => 'nullable|array',
            'postIds' => 'nullable|array'
        ], [
            'userName.required' => '用户名不能为空',
            'userName.string' => '用户名必须是字符串',
            'userName.max' => '用户名长度不能超过30个字符',
            'userName.unique' => '用户名已存在',

            'nickName.required' => '昵称不能为空',
            'nickName.string' => '昵称必须是字符串',
            'nickName.max' => '昵称长度不能超过30个字符',

            'password.required' => '密码不能为空',
            'password.string' => '密码必须是字符串',
            'password.min' => '密码长度不能少于6位',

            'email.email' => '邮箱格式不正确',
            'email.max' => '邮箱长度不能超过50个字符',

            'phonenumber.string' => '手机号必须是字符串',
            'phonenumber.max' => '手机号长度不能超过11个字符',

            'sex.in' => '性别取值范围不正确（只能是0、1或2）',

            'status.required' => '状态不能为空',
            'status.in' => '状态值不正确（只能是0或1）',

            'deptId.exists' => '部门ID不存在',

            'roleIds.array' => '角色ID必须是数组',
            'postIds.array' => '岗位ID必须是数组'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }
        $params = $request->all();

        try {
            // 开启事务
            DB::beginTransaction();

            $user = SystemUser::create([
                'user_name' => $params['userName'],
                'nick_name' => $params['nickName'],
                'password' => Hash::make($params['password']),
                'password_plain' => $params['password'],
                'email' => $params['email'] ?? '',
                'contact_email' => $params['contactEmail'] ?? '',
                'phonenumber' => $params['phonenumber'] ?? '',
                'sync_teching_user_flag' => 0,
                'sale_group_id' => $params['saleGroupId'] ?? 0,
                // 强制跳改密
                'force_change_password_flag' => 0,
                'sex' => $params['sex'] ?? '0',
                'status' => $params['status'] ?? 1,
                'dept_id' => $params['deptId'] ?? null,
                'create_by' => $request->user()->user_name,
                'create_time' => now()
            ]);

            // 分配角色
            if ($request->filled('roleIds')) {
                $user->roles()->sync($request->roleIds);
            }

            // 分配岗位
            if ($request->filled('postIds')) {
                $user->posts()->sync($request->postIds);
            }

            DB::commit();
            return ApiResponse::success();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('daily')->error('创建后台用户失败:' . $e->getMessage(), [
                'params' => $params,
                'msg' => $e->getMessage()
            ]);
            return ApiResponse::error();
        }
    }

    /**
     * 更新用户
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userId' => 'required|exists:sys_user,user_id',
            'userName' => 'required|string|max:30|unique:sys_user,user_name,' . $request->userId . ',user_id',
            'nickName' => 'required|string|max:30',
            'email' => 'nullable|email|max:50',
            'password' => 'required|string|min:6',
            'phonenumber' => 'nullable|string|max:11',
            'sex' => 'nullable|in:0,1,2',
            'status' => 'required|in:0,1',
            // todo 移除部门校验
            // 'deptId' => 'nullable|exists:sys_dept,dept_id',
            'roleIds' => 'nullable|array',
            'postIds' => 'nullable|array'
        ], [
            'userId.required' => '用户ID不能为空',
            'userId.exists' => '用户ID不存在',

            'userName.required' => '用户名不能为空',
            'userName.string' => '用户名必须为字符串',
            'userName.max' => '用户名长度不能超过30个字符',
            'userName.unique' => '用户名已存在',

            'nickName.required' => '用户昵称不能为空',
            'nickName.string' => '用户昵称必须为字符串',
            'nickName.max' => '用户昵称长度不能超过30个字符',

            'email.email' => '邮箱格式不正确',
            'email.max' => '邮箱长度不能超过50个字符',

            'password.required' => '密码不能为空',
            'password.string' => '密码必须为字符串',
            'password.min' => '密码至少需要6位字符',

            'phonenumber.string' => '手机号必须为字符串',
            'phonenumber.max' => '手机号最多11位',

            'sex.in' => '性别参数不合法',

            'status.required' => '状态不能为空',
            'status.in' => '状态参数不合法',

            'roleIds.array' => '角色ID列表必须为数组',
            'postIds.array' => '岗位ID列表必须为数组',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }

        $user = SystemUser::find($request->userId);

        $user->update([
            'user_name' => $request->userName,
            'nick_name' => $request->nickName,
            'email' => $request->email ?? '',
            'password' => Hash::make($request->password),
            'password_plain' => $request->password,
            'phonenumber' => $request->phonenumber ?? '',
            'sex' => $request->sex ?? '0',
            'status' => $request->status ?? 1,
            'dept_id' => $request->deptId ?? null,
            'update_by' => $request->user()->user_name ?? '',
            'update_time' => now()
        ]);

        // 更新角色
        if ($request->filled('roleIds')) {
            $user->roles()->sync($request->roleIds);
        }

        // 更新岗位
        if ($request->filled('postIds')) {
            $user->posts()->sync($request->postIds);
        }

        return ApiResponse::success([], '修改成功');
    }

    /**
     * 删除用户
     */
    public function destroy($userId)
    {
        $user = SystemUser::find($userId);

        if (!$user) {
            return ApiResponse::error('用户不存在');
        }

        if ($user->user_id == 1) {
            return ApiResponse::error('不能删除超级管理员');
        }

        $user->update([
            'del_flag' => '2',
            'update_by' => request()->user()->user_name,
            'update_time' => now()
        ]);

        return ApiResponse::success([], '删除成功');
    }

    /**
     * 重置密码
     */
    public function resetPwd(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userId' => 'required|exists:sys_user,user_id',
            'password' => 'required|string|min:6'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }

        $user = SystemUser::find($request->userId);

        $user->update([
            'password' => Hash::make($request->password),
            'update_by' => $request->user()->user_name,
            'update_time' => now()
        ]);

        return ApiResponse::success([], '重置成功');
    }

    /**
     * 修改用户状态
     */
    public function changeStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userId' => 'required|exists:sys_user,user_id',
            'status' => 'required|in:0,1'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }

        $user = SystemUser::find($request->userId);

        if ($user->user_id == 1) {
            return ApiResponse::error('不能停用超级管理员');
        }

        $user->update([
            'status' => $request->status,
            'update_by' => $request->user()->user_name,
            'update_time' => now()
        ]);

        return ApiResponse::success([], '修改成功');
    }

    /**
     * 获取部门树
     */
    public function deptTree()
    {
        $depts = SystemDept::where('status', '0')
            ->where('del_flag', '0')
            ->orderBy('parent_id')
            ->orderBy('order_num')
            ->get();

        $tree = SystemDept::buildDeptTree($depts);

        return ApiResponse::success(['data' => DeptResource::collection($tree)->resolve()], '查询成功');
    }

    /**
     * 查询授权角色
     */
    public function getAuthRole($userId)
    {
        $user = SystemUser::with(['roles'])->find($userId);

        if (!$user) {
            return ApiResponse::error('用户不存在');
        }

        // 获取所有角色
        $roles = SystemRole::where('status', '0')
            ->where('del_flag', '0')
            ->orderBy('role_sort')
            ->get();

        return ApiResponse::success([
            'user' => new UserResource($user),
            'roles' => RoleResource::collection($roles)->resolve()
        ], '查询成功');


        return response()->json([
            'code' => 200,
            'msg' => '查询成功',
            'user' => new UserResource($user),
            'roles' => RoleResource::collection($roles)->resolve()
        ]);
    }

    /**
     * 保存授权角色
     */
    public function updateAuthRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userId' => 'required|exists:sys_user,user_id',
            'roleIds' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }

        $user = SystemUser::find($request->userId);

        if ($user->user_id == 1) {
            return ApiResponse::error('不能修改超级管理员的角色');
        }

        // 解析角色ID
        $roleIds = [];
        if ($request->filled('roleIds')) {
            $roleIds = explode(',', $request->roleIds);

            // 验证角色是否存在
            $existingRoleIds = SystemRole::whereIn('role_id', $roleIds)
                ->where('status', '0')
                ->where('del_flag', '0')
                ->pluck('role_id')
                ->toArray();

            if (count($existingRoleIds) != count($roleIds)) {
                return ApiResponse::error('部分角色不存在或已被停用');
            }

            $roleIds = $existingRoleIds;
        }

        // 更新用户角色
        $user->roles()->sync($roleIds);

        return ApiResponse::success([], '授权成功');
    }

    /**
     * 递归获取子部门ID
     */
    private function getChildDeptIds($dept, &$deptIds)
    {
        $children = $dept->children;
        foreach ($children as $child) {
            $deptIds[] = $child->dept_id;
            $this->getChildDeptIds($child, $deptIds);
        }
    }
}
