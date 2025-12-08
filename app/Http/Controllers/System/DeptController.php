<?php

namespace App\Http\Controllers\System;

use App\Constant\ResponseCode;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\System\DeptResource;
use App\Models\System\SystemDept;
use App\Models\System\SystemUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DeptController extends Controller
{
    /**
     * 获取部门列表
     */
    public function list(Request $request)
    {
        $query = SystemDept::where('del_flag', '0');

        // 搜索条件
        if ($request->filled('deptName')) {
            $query->where('dept_name', 'like', '%' . $request->deptName . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $depts = $query->orderBy('parent_id')
                      ->orderBy('order_num')
                      ->get();

        // 构建部门树
        $deptTree = SystemDept::buildDeptTree($depts);
        // todo 返回格式不对
        $result = <<<EOF
{
    "msg": "操作成功",
    "code": 200,
    "data": [
        {
            "createBy": "admin",
            "createTime": "2025-05-26 10:07:40",
            "updateBy": null,
            "updateTime": null,
            "remark": null,
            "deptId": 100,
            "parentId": 0,
            "ancestors": "0",
            "deptName": "若依科技",
            "orderNum": 0,
            "leader": "若依",
            "phone": "15888888888",
            "email": "ry@qq.com",
            "status": "0",
            "delFlag": "0",
            "parentName": null,
            "children": []
        },
        {
            "createBy": "admin",
            "createTime": "2025-05-26 10:07:40",
            "updateBy": null,
            "updateTime": null,
            "remark": null,
            "deptId": 101,
            "parentId": 100,
            "ancestors": "0,100",
            "deptName": "深圳总公司",
            "orderNum": 1,
            "leader": "若依",
            "phone": "15888888888",
            "email": "ry@qq.com",
            "status": "0",
            "delFlag": "0",
            "parentName": null,
            "children": []
        },
        {
            "createBy": "admin",
            "createTime": "2025-05-26 10:07:41",
            "updateBy": null,
            "updateTime": null,
            "remark": null,
            "deptId": 102,
            "parentId": 100,
            "ancestors": "0,100",
            "deptName": "长沙分公司",
            "orderNum": 2,
            "leader": "若依",
            "phone": "15888888888",
            "email": "ry@qq.com",
            "status": "0",
            "delFlag": "0",
            "parentName": null,
            "children": []
        },
        {
            "createBy": "admin",
            "createTime": "2025-05-26 10:07:41",
            "updateBy": null,
            "updateTime": null,
            "remark": null,
            "deptId": 103,
            "parentId": 101,
            "ancestors": "0,100,101",
            "deptName": "研发部门",
            "orderNum": 1,
            "leader": "若依",
            "phone": "15888888888",
            "email": "ry@qq.com",
            "status": "0",
            "delFlag": "0",
            "parentName": null,
            "children": []
        },
        {
            "createBy": "admin",
            "createTime": "2025-05-26 10:07:42",
            "updateBy": null,
            "updateTime": null,
            "remark": null,
            "deptId": 104,
            "parentId": 101,
            "ancestors": "0,100,101",
            "deptName": "市场部门",
            "orderNum": 2,
            "leader": "若依",
            "phone": "15888888888",
            "email": "ry@qq.com",
            "status": "0",
            "delFlag": "0",
            "parentName": null,
            "children": []
        },
        {
            "createBy": "admin",
            "createTime": "2025-05-26 10:07:42",
            "updateBy": null,
            "updateTime": null,
            "remark": null,
            "deptId": 105,
            "parentId": 101,
            "ancestors": "0,100,101",
            "deptName": "测试部门",
            "orderNum": 3,
            "leader": "若依",
            "phone": "15888888888",
            "email": "ry@qq.com",
            "status": "0",
            "delFlag": "0",
            "parentName": null,
            "children": []
        },
        {
            "createBy": "admin",
            "createTime": "2025-05-26 10:07:42",
            "updateBy": null,
            "updateTime": null,
            "remark": null,
            "deptId": 106,
            "parentId": 101,
            "ancestors": "0,100,101",
            "deptName": "财务部门",
            "orderNum": 4,
            "leader": "若依",
            "phone": "15888888888",
            "email": "ry@qq.com",
            "status": "0",
            "delFlag": "0",
            "parentName": null,
            "children": []
        },
        {
            "createBy": "admin",
            "createTime": "2025-05-26 10:07:43",
            "updateBy": null,
            "updateTime": null,
            "remark": null,
            "deptId": 107,
            "parentId": 101,
            "ancestors": "0,100,101",
            "deptName": "运维部门",
            "orderNum": 5,
            "leader": "若依",
            "phone": "15888888888",
            "email": "ry@qq.com",
            "status": "0",
            "delFlag": "0",
            "parentName": null,
            "children": []
        },
        {
            "createBy": "admin",
            "createTime": "2025-05-26 10:07:43",
            "updateBy": null,
            "updateTime": null,
            "remark": null,
            "deptId": 108,
            "parentId": 102,
            "ancestors": "0,100,102",
            "deptName": "市场部门",
            "orderNum": 1,
            "leader": "若依",
            "phone": "15888888888",
            "email": "ry@qq.com",
            "status": "0",
            "delFlag": "0",
            "parentName": null,
            "children": []
        },
        {
            "createBy": "admin",
            "createTime": "2025-05-26 10:07:43",
            "updateBy": null,
            "updateTime": null,
            "remark": null,
            "deptId": 109,
            "parentId": 102,
            "ancestors": "0,100,102",
            "deptName": "财务部门",
            "orderNum": 2,
            "leader": "若依",
            "phone": "15888888888",
            "email": "ry@qq.com",
            "status": "0",
            "delFlag": "0",
            "parentName": null,
            "children": []
        }
    ]
}
EOF;


        return ApiResponse::success(['data' => DeptResource::collection($deptTree)->resolve()], '查询成功');
    }

    /**
     * 获取部门列表（排除节点）
     */
    public function excludeChild($deptId)
    {
        $depts = SystemDept::where('del_flag', '0')
                        ->where('dept_id', '!=', $deptId)
                        ->orderBy('parent_id')
                        ->orderBy('order_num')
                        ->get();

        // 过滤掉指定部门的所有子部门
        $excludeDept = SystemDept::find($deptId);
        if ($excludeDept) {
            $excludeIds = $this->getChildDeptIds($excludeDept);
            $excludeIds[] = $deptId;
            $depts = $depts->whereNotIn('dept_id', $excludeIds);
        }

        // 构建部门树
        $deptTree = SystemDept::buildDeptTree($depts);

        return ApiResponse::success(['data' => DeptResource::collection($deptTree)->resolve()], '查询成功');
    }

    /**
     * 获取部门详情
     */
    public function show($deptId)
    {
        $dept = SystemDept::where('dept_id', $deptId)
                      ->where('del_flag', '0')
                      ->first();

        if (!$dept) {
            throw new ApiException('部门不存在', ResponseCode::DATA_NOT_FOUND);
        }

        return ApiResponse::success(new DeptResource($dept), '查询成功');
    }

    /**
     * 新增部门
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'deptName' => 'required|string|max:30',
            'parentId' => 'required|integer',
            'orderNum' => 'required|integer',
            'leader' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:11',
            'email' => 'nullable|email|max:50',
            'status' => 'required|in:0,1'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }

        // 构建祖先列表
        $ancestors = '0';
        if ($request->parentId != 0) {
            $parentDept = SystemDept::find($request->parentId);
            if ($parentDept) {
                $ancestors = $parentDept->ancestors . ',' . $request->parentId;
            }
        }

        $dept = SystemDept::create([
            'parent_id' => $request->parentId,
            'ancestors' => $ancestors,
            'dept_name' => $request->deptName,
            'order_num' => $request->orderNum,
            'leader' => $request->leader,
            'phone' => $request->phone,
            'email' => $request->email,
            'status' => $request->status,
            'create_by' => $request->user()->user_name,
            'create_time' => now()
        ]);

        return ApiResponse::success([], '新增成功');
    }

    /**
     * 更新部门
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'deptId' => 'required|exists:sys_dept,dept_id',
            'deptName' => 'required|string|max:30',
            'parentId' => 'required|integer',
            'orderNum' => 'required|integer',
            'leader' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:11',
            'email' => 'nullable|email|max:50',
            'status' => 'required|in:0,1'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }

        $dept = SystemDept::find($request->deptId);

        // 检查是否将父部门设置为自己或子部门
        if ($request->parentId == $request->deptId) {
            throw new ApiException('上级部门不能选择自己', ResponseCode::OPERATION_FAILED);
        }

        // 检查是否选择了子部门作为父部门
        $childIds = $this->getChildDeptIds($dept);
        if (in_array($request->parentId, $childIds)) {
            throw new ApiException('上级部门不能选择自己的子部门', ResponseCode::OPERATION_FAILED);
        }

        // 构建新的祖先列表
        $ancestors = '0';
        if ($request->parentId != 0) {
            $parentDept = SystemDept::find($request->parentId);
            if ($parentDept) {
                $ancestors = $parentDept->ancestors . ',' . $request->parentId;
            }
        }

        $oldAncestors = $dept->ancestors;

        $dept->update([
            'parent_id' => $request->parentId,
            'ancestors' => $ancestors,
            'dept_name' => $request->deptName,
            'order_num' => $request->orderNum,
            'leader' => $request->leader,
            'phone' => $request->phone,
            'email' => $request->email,
            'status' => $request->status,
            'update_by' => $request->user()->user_name,
            'update_time' => now()
        ]);

        // 更新子部门的祖先列表
        if ($oldAncestors != $ancestors) {
            $this->updateChildrenAncestors($dept, $oldAncestors, $ancestors);
        }

        return ApiResponse::success([], '修改成功');
    }

    /**
     * 删除部门
     */
    public function destroy($deptId)
    {
        $dept = SystemDept::find($deptId);

        if (!$dept) {
            throw new ApiException('部门不存在', ResponseCode::DATA_NOT_FOUND);
        }

        // 检查是否存在子部门
        $childCount = SystemDept::where('parent_id', $deptId)
                            ->where('del_flag', '0')
                            ->count();
        if ($childCount > 0) {
            throw new ApiException('存在下级部门，不允许删除', ResponseCode::OPERATION_FAILED);
        }

        // 检查部门是否存在用户
        $userCount = SystemUser::where('dept_id', $deptId)
                           ->where('del_flag', '0')
                           ->count();
        if ($userCount > 0) {
            throw new ApiException('部门存在用户，不允许删除', ResponseCode::OPERATION_FAILED);
        }

        $dept->update([
            'del_flag' => '2',
            'update_by' => request()->user()->user_name,
            'update_time' => now()
        ]);

        return ApiResponse::success([], '删除成功');
    }

    /**
     * 递归获取子部门ID
     */
    private function getChildDeptIds($dept)
    {
        $childIds = [];
        $children = SystemDept::where('parent_id', $dept->dept_id)
                          ->where('del_flag', '0')
                          ->get();

        foreach ($children as $child) {
            $childIds[] = $child->dept_id;
            $childIds = array_merge($childIds, $this->getChildDeptIds($child));
        }

        return $childIds;
    }

    /**
     * 更新子部门的祖先列表
     */
    private function updateChildrenAncestors($dept, $oldAncestors, $newAncestors)
    {
        $children = SystemDept::where('parent_id', $dept->dept_id)
                          ->where('del_flag', '0')
                          ->get();

        foreach ($children as $child) {
            $childOldAncestors = $child->ancestors;
            $childNewAncestors = str_replace($oldAncestors, $newAncestors, $childOldAncestors);

            $child->update([
                'ancestors' => $childNewAncestors,
                'update_by' => request()->user()->user_name,
                'update_time' => now()
            ]);

            // 递归更新子部门的子部门
            $this->updateChildrenAncestors($child, $childOldAncestors, $childNewAncestors);
        }
    }
}
