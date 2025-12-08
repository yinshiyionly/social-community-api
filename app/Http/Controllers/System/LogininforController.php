<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\System\LoginInforResource;
use App\Models\System\SystemLogininfor;
use Illuminate\Http\Request;

class LogininforController extends Controller
{
    /**
     * 获取登录日志列表
     */
    public function list(Request $request)
    {
        $query = SystemLogininfor::query();

        // 搜索条件
        if ($request->filled('userName')) {
            $query->where('user_name', 'like', '%' . $request->userName . '%');
        }

        if ($request->filled('ipaddr')) {
            $query->where('ipaddr', 'like', '%' . $request->ipaddr . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 时间范围查询
        if ($request->filled('beginTime') && $request->filled('endTime')) {
            $query->whereBetween('login_time', [$request->beginTime, $request->endTime]);
        }

        $pageNum = $request->get('pageNum', 1);
        $pageSize = $request->get('pageSize', 10);

        $loginInfors = $query->orderBy('login_time', 'desc')->paginate($pageSize, ['*'], 'page', $pageNum);

        return ApiResponse::paginate($loginInfors, LoginInforResource::class, '查询成功');
    }

    /**
     * 删除登录日志
     */
    public function destroy($infoIds)
    {
        $ids = explode(',', $infoIds);

        $deletedCount = SystemLogininfor::whereIn('info_id', $ids)->delete();

        if ($deletedCount > 0) {
            return ApiResponse::success([], '删除成功');
        } else {
            return ApiResponse::error('删除失败，登录日志不存在');
        }
    }

    /**
     * 清空登录日志
     */
    public function clean()
    {
        SystemLogininfor::truncate();
        return ApiResponse::success([], '清空成功');
    }

    /**
     * 账户解锁
     */
    public function unlock($userName)
    {
        if (empty($userName)) {
            return ApiResponse::error('用户名不能为空');
        }

        // 这里可以实现账户解锁逻辑
        // 例如清除登录失败次数缓存等

        return ApiResponse::success([], '账户解锁成功');
    }

    /**
     * 导出登录日志
     */
    public function export(Request $request)
    {
        $query = SystemLogininfor::query();

        // 应用搜索条件
        if ($request->filled('userName')) {
            $query->where('user_name', 'like', '%' . $request->userName . '%');
        }

        if ($request->filled('ipaddr')) {
            $query->where('ipaddr', 'like', '%' . $request->ipaddr . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $loginInfors = $query->orderBy('login_time', 'desc')->get();

        return ApiResponse::success(LoginInforResource::collection($loginInfors)->resolve(), '导出成功');
    }
}
