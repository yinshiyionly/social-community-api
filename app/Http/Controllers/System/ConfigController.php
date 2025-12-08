<?php

namespace App\Http\Controllers\System;

use App\Constant\ResponseCode;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\System\ConfigResource;
use App\Models\System\SystemConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ConfigController extends Controller
{
    /**
     * 获取参数配置列表
     */
    public function list(Request $request)
    {
        $query = SystemConfig::query();

        // 搜索条件
        if ($request->filled('configName')) {
            $query->where('config_name', 'like', '%' . $request->configName . '%');
        }

        if ($request->filled('configKey')) {
            $query->where('config_key', 'like', '%' . $request->configKey . '%');
        }

        if ($request->filled('configType')) {
            $query->where('config_type', $request->configType);
        }

        // 时间范围查询
        if ($request->filled('beginTime') && $request->filled('endTime')) {
            $query->whereBetween('create_time', [$request->beginTime, $request->endTime]);
        }

        $pageNum = $request->get('pageNum', 1);
        $pageSize = $request->get('pageSize', 10);

        $configs = $query->orderBy('config_id')->paginate($pageSize, ['*'], 'page', $pageNum);

        return ApiResponse::paginate($configs, ConfigResource::class, '查询成功');
    }

    /**
     * 获取参数配置详情
     */
    public function show($configId)
    {
        $config = SystemConfig::find($configId);

        if (!$config) {
            throw new ApiException('参数配置不存在', ResponseCode::DATA_NOT_FOUND);
        }

        return ApiResponse::success(new ConfigResource($config), '查询成功');
    }

    /**
     * 根据参数键名查询参数值
     */
    public function configKey($configKey)
    {
        $configValue = SystemConfig::getConfigByKey($configKey);

        if ($configValue === null) {
            throw new ApiException('参数不存在', ResponseCode::DATA_NOT_FOUND);
        }

        // ruoyi-vue 特殊接口不使用框架统一返回
        // return ApiResponse::success($configValue, '查询成功');
        $response = [
            'code' => ResponseCode::SUCCESS,
            'msg' => $configValue
        ];

        return response()->json($response);
    }

    /**
     * 新增参数配置
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'configName' => 'required|string|max:100',
            'configKey' => 'required|string|max:100|unique:sys_config,config_key|regex:/^[a-zA-Z0-9._-]+$/',
            'configValue' => 'required|string|max:500',
            'configType' => 'required|in:Y,N',
            'remark' => 'nullable|string|max:500'
        ], [
            'configName.required' => '参数名称不能为空',
            'configKey.required' => '参数键名不能为空',
            'configKey.unique' => '参数键名已存在',
            'configKey.regex' => '参数键名只能包含字母、数字、点、下划线和横线',
            'configValue.required' => '参数键值不能为空',
            'configType.required' => '系统内置不能为空',
            'configType.in' => '系统内置值不正确'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }

        $config = SystemConfig::create([
            'config_name' => $request->configName,
            'config_key' => $request->configKey,
            'config_value' => $request->configValue,
            'config_type' => $request->configType,
            'create_by' => $request->user()->user_name,
            'create_time' => now(),
            'remark' => $request->remark
        ]);

        return ApiResponse::success([], '新增成功');
    }

    /**
     * 更新参数配置
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'configId' => 'required|exists:sys_config,config_id',
            'configName' => 'required|string|max:100',
            'configKey' => 'required|string|max:100|unique:sys_config,config_key,' . $request->configId . ',config_id|regex:/^[a-zA-Z0-9._-]+$/',
            'configValue' => 'required|string|max:500',
            'configType' => 'required|in:Y,N',
            'remark' => 'nullable|string|max:500'
        ], [
            'configName.required' => '参数名称不能为空',
            'configKey.required' => '参数键名不能为空',
            'configKey.unique' => '参数键名已存在',
            'configKey.regex' => '参数键名只能包含字母、数字、点、下划线和横线',
            'configValue.required' => '参数键值不能为空',
            'configType.required' => '系统内置不能为空',
            'configType.in' => '系统内置值不正确'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first());
        }

        $config = SystemConfig::find($request->configId);

        $config->update([
            'config_name' => $request->configName,
            'config_key' => $request->configKey,
            'config_value' => $request->configValue,
            'config_type' => $request->configType,
            'update_by' => $request->user()->user_name,
            'update_time' => now(),
            'remark' => $request->remark
        ]);

        return ApiResponse::success([], '修改成功');
    }

    /**
     * 删除参数配置
     */
    public function destroy($configIds)
    {
        $ids = explode(',', $configIds);

        // 检查是否包含系统内置参数
        $systemConfigs = SystemConfig::whereIn('config_id', $ids)
                                 ->where('config_type', 'Y')
                                 ->pluck('config_name')
                                 ->toArray();

        if (!empty($systemConfigs)) {
            throw new ApiException('参数【' . implode(',', $systemConfigs) . '】属于系统内置，不能删除', ResponseCode::OPERATION_FAILED);
        }

        $deletedCount = SystemConfig::whereIn('config_id', $ids)->delete();

        if ($deletedCount > 0) {
            return ApiResponse::success([], '删除成功');
        } else {
            throw new ApiException('删除失败，参数配置不存在', ResponseCode::DATA_NOT_FOUND);
        }
    }

    /**
     * 刷新参数缓存
     */
    public function refreshCache()
    {
        SystemConfig::clearConfigCache();
        return ApiResponse::success([], '刷新成功');
    }

    /**
     * 导出参数配置
     */
    public function export(Request $request)
    {
        $query = SystemConfig::query();

        // 应用搜索条件
        if ($request->filled('configName')) {
            $query->where('config_name', 'like', '%' . $request->configName . '%');
        }

        if ($request->filled('configKey')) {
            $query->where('config_key', 'like', '%' . $request->configKey . '%');
        }

        if ($request->filled('configType')) {
            $query->where('config_type', $request->configType);
        }

        $configs = $query->orderBy('config_id')->get();

        return ApiResponse::success(ConfigResource::collection($configs)->resolve(), '导出成功');
    }
}
