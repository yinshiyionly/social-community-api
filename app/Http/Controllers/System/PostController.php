<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\System\PostResource;
use App\Models\System\SystemPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    /**
     * 获取岗位列表
     */
    public function list(Request $request)
    {
        $query = SystemPost::query();

        // 搜索条件
        if ($request->filled('postCode')) {
            $query->where('post_code', 'like', '%' . $request->postCode . '%');
        }

        if ($request->filled('postName')) {
            $query->where('post_name', 'like', '%' . $request->postName . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 时间范围查询
        if ($request->filled('beginTime') && $request->filled('endTime')) {
            $query->whereBetween('create_time', [$request->beginTime, $request->endTime]);
        }

        $pageNum = $request->get('pageNum', 1);
        $pageSize = $request->get('pageSize', 10);

        $posts = $query->orderBy('post_sort')->paginate($pageSize, ['*'], 'page', $pageNum);

        return ApiResponse::paginate($posts, PostResource::class, '查询成功');
    }

    /**
     * 获取岗位详情
     */
    public function show($postId)
    {
        $post = SystemPost::find($postId);

        if (!$post) {
            return ApiResponse::error('岗位不存在');
        }

        return ApiResponse::success(new PostResource($post), '查询成功');
    }

    /**
     * 新增岗位
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'postCode' => 'required|string|max:64|unique:sys_post,post_code',
            'postName' => 'required|string|max:50',
            'postSort' => 'required|integer|min:0',
            'status' => 'required|in:0,1',
            'remark' => 'nullable|string|max:500'
        ], [
            'postCode.required' => '岗位编码不能为空',
            'postCode.unique' => '岗位编码已存在',
            'postName.required' => '岗位名称不能为空',
            'postSort.required' => '显示顺序不能为空',
            'postSort.integer' => '显示顺序必须为数字',
            'postSort.min' => '显示顺序不能小于0',
            'status.required' => '状态不能为空',
            'status.in' => '状态值不正确'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }

        // 检查岗位名称是否重复
        if (SystemPost::where('post_name', $request->postName)->exists()) {
            return ApiResponse::error('岗位名称已存在');
        }

        $post = SystemPost::create([
            'post_code' => $request->postCode,
            'post_name' => $request->postName,
            'post_sort' => $request->postSort,
            'status' => $request->status,
            'create_by' => $request->user()->user_name,
            'create_time' => now(),
            'remark' => $request->remark
        ]);

        return ApiResponse::success([], '新增成功');
    }

    /**
     * 更新岗位
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'postId' => 'required|exists:sys_post,post_id',
            'postCode' => 'required|string|max:64|unique:sys_post,post_code,' . $request->postId . ',post_id',
            'postName' => 'required|string|max:50',
            'postSort' => 'required|integer|min:0',
            'status' => 'required|in:0,1',
            'remark' => 'nullable|string|max:500'
        ], [
            'postCode.required' => '岗位编码不能为空',
            'postCode.unique' => '岗位编码已存在',
            'postName.required' => '岗位名称不能为空',
            'postSort.required' => '显示顺序不能为空',
            'postSort.integer' => '显示顺序必须为数字',
            'postSort.min' => '显示顺序不能小于0',
            'status.required' => '状态不能为空',
            'status.in' => '状态值不正确'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }

        $post = SystemPost::find($request->postId);

        // 检查岗位名称是否重复（排除自己）
        if (SystemPost::where('post_name', $request->postName)
                   ->where('post_id', '!=', $request->postId)
                   ->exists()) {
            return ApiResponse::error('岗位名称已存在');
        }

        $post->update([
            'post_code' => $request->postCode,
            'post_name' => $request->postName,
            'post_sort' => $request->postSort,
            'status' => $request->status,
            'update_by' => $request->user()->user_name,
            'update_time' => now(),
            'remark' => $request->remark
        ]);

        return ApiResponse::success([], '修改成功');
    }

    /**
     * 删除岗位
     */
    public function destroy($postIds)
    {
        $ids = explode(',', $postIds);

        // 检查岗位是否被用户使用
        $usedPosts = SystemPost::whereIn('post_id', $ids)
                           ->whereHas('users')
                           ->pluck('post_name')
                           ->toArray();

        if (!empty($usedPosts)) {
            return ApiResponse::error('岗位【' . implode(',', $usedPosts) . '】已分配用户，不能删除');
        }

        $deletedCount = SystemPost::whereIn('post_id', $ids)->delete();

        if ($deletedCount > 0) {
            return ApiResponse::success([], '删除成功');
        } else {
            return ApiResponse::error('删除失败，岗位不存在');
        }
    }

    /**
     * 导出岗位数据
     */
    public function export(Request $request)
    {
        $query = SystemPost::query();

        // 应用搜索条件
        if ($request->filled('postCode')) {
            $query->where('post_code', 'like', '%' . $request->postCode . '%');
        }

        if ($request->filled('postName')) {
            $query->where('post_name', 'like', '%' . $request->postName . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $posts = $query->orderBy('post_sort')->get();

        // 这里可以集成Excel导出功能
        // 简化实现，返回数据
        return ApiResponse::success(PostResource::collection($posts)->resolve(), '导出成功');
    }

    /**
     * 获取岗位选项列表
     */
    public function optionselect()
    {
        $posts = SystemPost::where('status', '0')
                       ->orderBy('post_sort')
                       ->get(['post_id', 'post_name', 'post_code']);

        return ApiResponse::success(PostResource::collection($posts)->resolve(), '查询成功');
    }
}
