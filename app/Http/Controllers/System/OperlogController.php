<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\System\OperLogResource;
use App\Models\System\SystemOperLog;
use Illuminate\Http\Request;

class OperlogController extends Controller
{
    /**
     * 获取操作日志列表
     */
    public function list(Request $request)
    {
        $query = SystemOperLog::query();

        // 搜索条件
        if ($request->filled('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }

        if ($request->filled('operName')) {
            $query->where('oper_name', 'like', '%' . $request->operName . '%');
        }

        if ($request->filled('businessType')) {
            $query->where('business_type', $request->businessType);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('operIp')) {
            $query->where('oper_ip', 'like', '%' . $request->operIp . '%');
        }

        // 时间范围查询
        if ($request->filled('beginTime') && $request->filled('endTime')) {
            $query->whereBetween('oper_time', [$request->beginTime, $request->endTime]);
        }

        $pageNum = $request->get('pageNum', 1);
        $pageSize = $request->get('pageSize', 10);

        $operLogs = $query->orderBy('oper_time', 'desc')->paginate($pageSize, ['*'], 'page', $pageNum);

        return ApiResponse::paginate($operLogs, OperLogResource::class, '查询成功');
    }

    /**
     * 获取操作日志详情
     */
    public function show($operId)
    {
        $operLog = SystemOperLog::find($operId);

        if (!$operLog) {
            return ApiResponse::error('操作日志不存在');
        }

        return ApiResponse::success(new OperLogResource($operLog), '查询成功');
    }

    /**
     * 删除操作日志
     */
    public function destroy($operIds)
    {
        $ids = explode(',', $operIds);

        $deletedCount = SystemOperLog::whereIn('oper_id', $ids)->delete();

        if ($deletedCount > 0) {
            return ApiResponse::success([], '删除成功');
        } else {
            return ApiResponse::error('删除失败，操作日志不存在');
        }
    }

    /**
     * 清空操作日志
     */
    public function clean()
    {
        SystemOperLog::truncate();
        return ApiResponse::success([], '清空成功');
    }

    /**
     * 导出操作日志
     */
    public function export(Request $request)
    {
        $query = SystemOperLog::query();

        // 应用搜索条件
        if ($request->filled('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }

        if ($request->filled('operName')) {
            $query->where('oper_name', 'like', '%' . $request->operName . '%');
        }

        if ($request->filled('businessType')) {
            $query->where('business_type', $request->businessType);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $operLogs = $query->orderBy('oper_time', 'desc')->get();

        return ApiResponse::success(OperLogResource::collection($operLogs)->resolve(), '导出成功');
    }
}
