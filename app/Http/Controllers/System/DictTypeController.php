<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\System\DictTypeResource;
use App\Models\System\SystemDictType;
use App\Models\System\SystemDictData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DictTypeController extends Controller
{
    /**
     * 获取字典类型列表
     */
    public function list(Request $request)
    {
        $query = SystemDictType::query();

        // 搜索条件
        if ($request->filled('dictName')) {
            $query->where('dict_name', 'like', '%' . $request->dictName . '%');
        }

        if ($request->filled('dictType')) {
            $query->where('dict_type', 'like', '%' . $request->dictType . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 时间范围查询
        if ($request->filled('beginTime') && $request->filled('endTime')) {
            $query->whereBetween('create_time', [$request->beginTime, $request->endTime]);
        }

        // 接受分页参数
        $pageNum = $request->get('pageNum', 1);
        $pageSize = $request->get('pageSize', 10);

        $dictTypes = $query->orderBy('dict_id')->paginate($pageSize, ['*'], 'page', $pageNum);

        return ApiResponse::paginate($dictTypes, DictTypeResource::class, '查询成功');
    }

    /**
     * 获取字典类型详情
     */
    public function show($dictId)
    {
        $dictType = SystemDictType::find($dictId);

        if (!$dictType) {
            return ApiResponse::error('字典类型不存在');
        }

        return ApiResponse::success(['data' =>new DictTypeResource($dictType)], '查询成功');
    }

    /**
     * 新增字典类型
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dictName' => 'required|string|max:100',
            'dictType' => 'required|string|max:100|unique:sys_dict_type,dict_type|regex:/^[a-z_]+$/',
            'status' => 'required|in:0,1',
            'remark' => 'nullable|string|max:500'
        ], [
            'dictName.required' => '字典名称不能为空',
            'dictType.required' => '字典类型不能为空',
            'dictType.unique' => '字典类型已存在',
            'dictType.regex' => '字典类型只能包含小写字母和下划线',
            'status.required' => '状态不能为空',
            'status.in' => '状态值不正确'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }

        $dictType = SystemDictType::create([
            'dict_name' => $request->dictName,
            'dict_type' => $request->dictType,
            'status' => $request->status,
            'create_by' => $request->user()->user_name,
            'create_time' => now(),
            'remark' => $request->remark
        ]);

        return ApiResponse::success([], '新增成功');
    }

    /**
     * 更新字典类型
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dictId' => 'required|exists:sys_dict_type,dict_id',
            'dictName' => 'required|string|max:100',
            'dictType' => 'required|string|max:100|unique:sys_dict_type,dict_type,' . $request->dictId . ',dict_id|regex:/^[a-z_]+$/',
            'status' => 'required|in:0,1',
            'remark' => 'nullable|string|max:500'
        ], [
            'dictName.required' => '字典名称不能为空',
            'dictType.required' => '字典类型不能为空',
            'dictType.unique' => '字典类型已存在',
            'dictType.regex' => '字典类型只能包含小写字母和下划线',
            'status.required' => '状态不能为空',
            'status.in' => '状态值不正确'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }

        $dictType = SystemDictType::find($request->dictId);
        $oldDictType = $dictType->dict_type;

        $dictType->update([
            'dict_name' => $request->dictName,
            'dict_type' => $request->dictType,
            'status' => $request->status,
            'update_by' => $request->user()->user_name,
            'update_time' => now(),
            'remark' => $request->remark
        ]);

        // 如果字典类型发生变化，需要同步更新字典数据表
        if ($oldDictType !== $request->dictType) {
            SystemDictData::where('dict_type', $oldDictType)
                      ->update(['dict_type' => $request->dictType]);
        }

        return ApiResponse::success([], '修改成功');
    }

    /**
     * 删除字典类型
     */
    public function destroy($dictIds)
    {
        $ids = explode(',', $dictIds);

        // 检查是否存在字典数据
        $dictTypes = SystemDictType::whereIn('dict_id', $ids)->get();

        foreach ($dictTypes as $dictType) {
            $dataCount = SystemDictData::where('dict_type', $dictType->dict_type)->count();
            if ($dataCount > 0) {
                return ApiResponse::error('字典类型【' . $dictType->dict_name . '】已分配字典数据，不能删除');
            }
        }

        $deletedCount = SystemDictType::whereIn('dict_id', $ids)->delete();

        if ($deletedCount > 0) {
            return ApiResponse::success([], '删除成功');
        } else {
            return ApiResponse::error('删除失败，字典类型不存在');
        }
    }

    /**
     * 刷新字典缓存
     */
    public function refreshCache()
    {
        // 这里可以实现字典缓存刷新逻辑
        // 简化实现
        return ApiResponse::success([], '刷新成功');
    }

    /**
     * 获取字典选择框列表
     */
    public function optionselect()
    {
        $dictTypes = SystemDictType::where('status', '0')
                               ->orderBy('dict_id')
                               ->get(['dict_id', 'dict_name', 'dict_type']);

        return ApiResponse::success(DictTypeResource::collection($dictTypes)->resolve(), '查询成功');
    }

    /**
     * 导出字典类型
     */
    public function export(Request $request)
    {
        $query = SystemDictType::query();

        // 应用搜索条件
        if ($request->filled('dictName')) {
            $query->where('dict_name', 'like', '%' . $request->dictName . '%');
        }

        if ($request->filled('dictType')) {
            $query->where('dict_type', 'like', '%' . $request->dictType . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $dictTypes = $query->orderBy('dict_id')->get();

        return ApiResponse::success(DictTypeResource::collection($dictTypes)->resolve(), '导出成功');
    }
}
