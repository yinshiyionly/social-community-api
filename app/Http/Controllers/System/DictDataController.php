<?php

namespace App\Http\Controllers\System;

use App\Constant\ResponseCode;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\System\DictDataResource;
use App\Models\System\SystemDictData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DictDataController extends Controller
{
    /**
     * 获取字典数据列表
     */
    public function list(Request $request)
    {
        $query = SystemDictData::query();

        // 搜索条件
        if ($request->filled('dictType')) {
            $query->where('dict_type', $request->dictType);
        }

        if ($request->filled('dictLabel')) {
            $query->where('dict_label', 'like', '%' . $request->dictLabel . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $pageNum = $request->get('pageNum', 1);
        $pageSize = $request->get('pageSize', 10);

        $dictData = $query->orderBy('dict_sort')->paginate($pageSize, ['*'], 'page', $pageNum);

        return ApiResponse::paginate($dictData, DictDataResource::class, '查询成功');
    }

    /**
     * 根据字典类型查询字典数据信息
     */
    public function type($dictType)
    {
        $dictData = SystemDictData::where('dict_type', $dictType)
                              ->where('status', '0')
                              ->orderBy('dict_sort')
                              ->get();

        return ApiResponse::success(['data'=> DictDataResource::collection($dictData)->resolve()], '查询成功');
    }

    /**
     * 获取字典数据详情
     */
    public function show($dictCode)
    {
        $dictData = SystemDictData::find($dictCode);

        if (!$dictData) {
            throw new ApiException('字典数据不存在', ResponseCode::DATA_NOT_FOUND);
        }

        return ApiResponse::success(new DictDataResource($dictData), '查询成功');
    }

    /**
     * 新增字典数据
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dictLabel' => 'required|string|max:100',
            'dictValue' => 'required|string|max:100',
            'dictType' => 'required|string|max:100|exists:sys_dict_type,dict_type',
            'dictSort' => 'required|integer|min:0',
            'cssClass' => 'nullable|string|max:100',
            'listClass' => 'nullable|string|max:100',
            'isDefault' => 'required|in:Y,N',
            'status' => 'required|in:0,1',
            'remark' => 'nullable|string|max:500'
        ], [
            'dictLabel.required' => '数据标签不能为空',
            'dictValue.required' => '数据键值不能为空',
            'dictType.required' => '字典类型不能为空',
            'dictType.exists' => '字典类型不存在',
            'dictSort.required' => '数据顺序不能为空',
            'dictSort.integer' => '数据顺序必须为数字',
            'dictSort.min' => '数据顺序不能小于0',
            'isDefault.required' => '是否默认不能为空',
            'isDefault.in' => '是否默认值不正确',
            'status.required' => '状态不能为空',
            'status.in' => '状态值不正确'
        ]);

        if ($validator->fails()) {
            throw new ApiException($validator->errors()->first(), ResponseCode::INVALID_PARAMS);
        }

        // 检查同一字典类型下的键值是否重复
        if (SystemDictData::where('dict_type', $request->dictType)
                       ->where('dict_value', $request->dictValue)
                       ->exists()) {
            throw new ApiException('字典键值在该类型下已存在', ResponseCode::DATA_ALREADY_EXISTS);
        }

        // 如果设置为默认值，需要将同类型的其他数据设为非默认
        if ($request->isDefault === 'Y') {
            SystemDictData::where('dict_type', $request->dictType)
                      ->update(['is_default' => 'N']);
        }

        $dictData = SystemDictData::create([
            'dict_sort' => $request->dictSort,
            'dict_label' => $request->dictLabel,
            'dict_value' => $request->dictValue,
            'dict_type' => $request->dictType,
            'css_class' => $request->cssClass,
            'list_class' => $request->listClass ?: 'default',
            'is_default' => $request->isDefault,
            'status' => $request->status,
            'create_by' => $request->user()->user_name,
            'create_time' => now(),
            'remark' => $request->remark
        ]);

        return ApiResponse::success([], '新增成功');
    }

    /**
     * 更新字典数据
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dictCode' => 'required|exists:sys_dict_data,dict_code',
            'dictLabel' => 'required|string|max:100',
            'dictValue' => 'required|string|max:100',
            'dictType' => 'required|string|max:100|exists:sys_dict_type,dict_type',
            'dictSort' => 'required|integer|min:0',
            'cssClass' => 'nullable|string|max:100',
            'listClass' => 'nullable|string|max:100',
            'isDefault' => 'required|in:Y,N',
            'status' => 'required|in:0,1',
            'remark' => 'nullable|string|max:500'
        ], [
            'dictLabel.required' => '数据标签不能为空',
            'dictValue.required' => '数据键值不能为空',
            'dictType.required' => '字典类型不能为空',
            'dictType.exists' => '字典类型不存在',
            'dictSort.required' => '数据顺序不能为空',
            'dictSort.integer' => '数据顺序必须为数字',
            'dictSort.min' => '数据顺序不能小于0',
            'isDefault.required' => '是否默认不能为空',
            'isDefault.in' => '是否默认值不正确',
            'status.required' => '状态不能为空',
            'status.in' => '状态值不正确'
        ]);

        if ($validator->fails()) {
            throw new ApiException($validator->errors()->first(), ResponseCode::INVALID_PARAMS);
        }

        $dictData = SystemDictData::find($request->dictCode);

        // 检查同一字典类型下的键值是否重复（排除自己）
        if (SystemDictData::where('dict_type', $request->dictType)
                       ->where('dict_value', $request->dictValue)
                       ->where('dict_code', '!=', $request->dictCode)
                       ->exists()) {
            throw new ApiException('字典键值在该类型下已存在', ResponseCode::DATA_ALREADY_EXISTS);
        }

        // 如果设置为默认值，需要将同类型的其他数据设为非默认
        if ($request->isDefault === 'Y') {
            SystemDictData::where('dict_type', $request->dictType)
                      ->where('dict_code', '!=', $request->dictCode)
                      ->update(['is_default' => 'N']);
        }

        $dictData->update([
            'dict_sort' => $request->dictSort,
            'dict_label' => $request->dictLabel,
            'dict_value' => $request->dictValue,
            'dict_type' => $request->dictType,
            'css_class' => $request->cssClass,
            'list_class' => $request->listClass ?: 'default',
            'is_default' => $request->isDefault,
            'status' => $request->status,
            'update_by' => $request->user()->user_name,
            'update_time' => now(),
            'remark' => $request->remark
        ]);

        return ApiResponse::success([], '修改成功');
    }

    /**
     * 删除字典数据
     */
    public function destroy($dictCodes)
    {
        $codes = explode(',', $dictCodes);

        $deletedCount = SystemDictData::whereIn('dict_code', $codes)->delete();

        if ($deletedCount > 0) {
            return ApiResponse::success([], '删除成功');
        } else {
            throw new ApiException('删除失败，字典数据不存在', ResponseCode::DATA_NOT_FOUND);
        }
    }

    /**
     * 导出字典数据
     */
    public function export(Request $request)
    {
        $query = SystemDictData::query();

        // 应用搜索条件
        if ($request->filled('dictType')) {
            $query->where('dict_type', $request->dictType);
        }

        if ($request->filled('dictLabel')) {
            $query->where('dict_label', 'like', '%' . $request->dictLabel . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $dictData = $query->orderBy('dict_sort')->get();

        return ApiResponse::success(DictDataResource::collection($dictData)->resolve(), '导出成功');
    }
}
