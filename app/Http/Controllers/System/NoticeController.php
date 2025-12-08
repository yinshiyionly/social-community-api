<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\System\NoticeResource;
use App\Models\System\SystemNotice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NoticeController extends Controller
{
    /**
     * 获取通知公告列表
     */
    public function list(Request $request)
    {
        $query = SystemNotice::query();

        // 搜索条件
        if ($request->filled('noticeTitle')) {
            $query->where('notice_title', 'like', '%' . $request->noticeTitle . '%');
        }

        if ($request->filled('noticeType')) {
            $query->where('notice_type', $request->noticeType);
        }

        if ($request->filled('createBy')) {
            $query->where('create_by', 'like', '%' . $request->createBy . '%');
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

        $notices = $query->orderBy('create_time', 'desc')->paginate($pageSize, ['*'], 'page', $pageNum);

        return ApiResponse::paginate($notices, NoticeResource::class, '查询成功');
    }

    /**
     * 获取通知公告详情
     */
    public function show($noticeId)
    {
        $notice = SystemNotice::find($noticeId);

        if (!$notice) {
            return ApiResponse::error('通知公告不存在');
        }

        return ApiResponse::success(['data' => new NoticeResource($notice)], '查询成功');
    }

    /**
     * 新增通知公告
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'noticeTitle' => 'required|string|max:50',
            'noticeType' => 'required|in:1,2',
            'noticeContent' => 'nullable|string',
            'status' => 'required|in:0,1',
            'remark' => 'nullable|string|max:255'
        ], [
            'noticeTitle.required' => '公告标题不能为空',
            'noticeTitle.max' => '公告标题不能超过50个字符',
            'noticeType.required' => '公告类型不能为空',
            'noticeType.in' => '公告类型值不正确',
            'status.required' => '公告状态不能为空',
            'status.in' => '公告状态值不正确'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }

        $notice = SystemNotice::create([
            'notice_title' => $request->noticeTitle,
            'notice_type' => $request->noticeType,
            'notice_content' => $request->noticeContent,
            'status' => $request->status,
            'create_by' => $request->user()->user_name,
            'create_time' => now(),
            'remark' => $request->remark
        ]);

        return ApiResponse::success([], '新增成功');
    }

    /**
     * 更新通知公告
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'noticeId' => 'required|exists:sys_notice,notice_id',
            'noticeTitle' => 'required|string|max:50',
            'noticeType' => 'required|in:1,2',
            'noticeContent' => 'nullable|string',
            'status' => 'required|in:0,1',
            'remark' => 'nullable|string|max:255'
        ], [
            'noticeTitle.required' => '公告标题不能为空',
            'noticeTitle.max' => '公告标题不能超过50个字符',
            'noticeType.required' => '公告类型不能为空',
            'noticeType.in' => '公告类型值不正确',
            'status.required' => '公告状态不能为空',
            'status.in' => '公告状态值不正确'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }

        $notice = SystemNotice::find($request->noticeId);

        $notice->update([
            'notice_title' => $request->noticeTitle,
            'notice_type' => $request->noticeType,
            'notice_content' => $request->noticeContent,
            'status' => $request->status,
            'update_by' => $request->user()->user_name,
            'update_time' => now(),
            'remark' => $request->remark
        ]);

        return ApiResponse::success([], '修改成功');
    }

    /**
     * 删除通知公告
     */
    public function destroy($noticeIds)
    {
        $ids = explode(',', $noticeIds);

        $deletedCount = SystemNotice::whereIn('notice_id', $ids)->delete();

        if ($deletedCount > 0) {
            return ApiResponse::success([], '删除成功');
        } else {
            return ApiResponse::error('删除失败，通知公告不存在');
        }
    }

    /**
     * 导出通知公告
     */
    public function export(Request $request)
    {
        $query = SystemNotice::query();

        // 应用搜索条件
        if ($request->filled('noticeTitle')) {
            $query->where('notice_title', 'like', '%' . $request->noticeTitle . '%');
        }

        if ($request->filled('noticeType')) {
            $query->where('notice_type', $request->noticeType);
        }

        if ($request->filled('createBy')) {
            $query->where('create_by', 'like', '%' . $request->createBy . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $notices = $query->orderBy('create_time', 'desc')->get();

        return ApiResponse::success(NoticeResource::collection($notices)->resolve(), '导出成功');
    }
}
