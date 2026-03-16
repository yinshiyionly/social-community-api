<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AppConfigStatusRequest;
use App\Http\Requests\Admin\AppConfigStoreRequest;
use App\Http\Requests\Admin\AppConfigUpdateRequest;
use App\Http\Resources\Admin\AppConfigListResource;
use App\Http\Resources\Admin\AppConfigResource;
use App\Http\Resources\ApiResponse;
use App\Services\Admin\AppConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Admin App 配置管理控制器。
 *
 * 职责：
 * 1. 提供 app_config 的增删改查接口；
 * 2. 统一处理请求字段映射与响应结构；
 * 3. 捕获异常后记录日志并返回通用错误，避免泄露内部实现细节。
 */
class AppConfigController extends Controller
{
    /**
     * @var AppConfigService
     */
    protected $appConfigService;

    /**
     * AppConfigController constructor.
     *
     * @param AppConfigService $appConfigService
     */
    public function __construct(AppConfigService $appConfigService)
    {
        $this->appConfigService = $appConfigService;
    }

    /**
     * 配置列表。
     *
     * 接口用途：
     * - 后台分页查询 App 配置；
     * - 支持按 configKey/groupName/env/platform/isEnabled 筛选。
     *
     * 关键输入：
     * - pageNum/pageSize 分页参数；
     * - configKey/groupName/env/platform/isEnabled 可选筛选。
     *
     * 关键输出：
     * - 返回 ApiResponse::paginate 结构（code/msg/total/rows）；
     * - rows 字段由 AppConfigListResource 输出。
     *
     * 失败分支：
     * - 异常统一记录日志后返回通用错误，避免暴露内部 SQL/栈信息。
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request)
    {
        try {
            $filters = [
                'configKey' => $request->input('configKey'),
                'groupName' => $request->input('groupName'),
                'env' => $request->input('env'),
                'platform' => $request->input('platform'),
                'isEnabled' => $request->input('isEnabled'),
            ];

            $pageNum = (int) $request->input('pageNum', 1);
            $pageSize = (int) $request->input('pageSize', 10);

            $paginator = $this->appConfigService->getList($filters, $pageNum, $pageSize);

            return ApiResponse::paginate($paginator, AppConfigListResource::class, '查询成功');
        } catch (\Exception $e) {
            Log::error('查询 App 配置列表失败', [
                'action' => 'list',
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 配置详情。
     *
     * @param int $configId 配置ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $configId)
    {
        try {
            $config = $this->appConfigService->getDetail($configId);

            if (!$config) {
                return ApiResponse::error('配置不存在');
            }

            return ApiResponse::resource($config, AppConfigResource::class, '查询成功');
        } catch (\Exception $e) {
            Log::error('查询 App 配置详情失败', [
                'action' => 'show',
                'config_id' => $configId,
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 新增配置。
     *
     * 字段映射：
     * - visibilityMode/timezone/windows -> visibility_rule；
     * - configKey/configName 等 camelCase 字段映射为表内 snake_case。
     *
     * @param AppConfigStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(AppConfigStoreRequest $request)
    {
        try {
            $this->appConfigService->create($request->validated());

            return ApiResponse::success([], '新增成功');
        } catch (\Exception $e) {
            Log::error('新增 App 配置失败', [
                'action' => 'store',
                'payload' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 更新配置。
     *
     * @param AppConfigUpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(AppConfigUpdateRequest $request)
    {
        try {
            $configId = (int) $request->input('configId');
            $data = $request->validated();
            unset($data['configId']);

            $updated = $this->appConfigService->update($configId, $data);

            if (!$updated) {
                return ApiResponse::error('配置不存在');
            }

            return ApiResponse::success([], '修改成功');
        } catch (\Exception $e) {
            Log::error('更新 App 配置失败', [
                'action' => 'update',
                'config_id' => $request->input('configId'),
                'payload' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 修改配置启用状态。
     *
     * @param AppConfigStatusRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeStatus(AppConfigStatusRequest $request)
    {
        try {
            $configId = (int) $request->input('configId');
            $isEnabled = (bool) $request->input('isEnabled');

            $updated = $this->appConfigService->changeStatus($configId, $isEnabled);

            if (!$updated) {
                return ApiResponse::error('配置不存在');
            }

            return ApiResponse::success([], '修改成功');
        } catch (\Exception $e) {
            Log::error('修改 App 配置状态失败', [
                'action' => 'changeStatus',
                'config_id' => $request->input('configId'),
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 删除配置（仅单条）。
     *
     * @param int $configId 配置ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $configId)
    {
        try {
            $deleted = $this->appConfigService->delete($configId);

            if (!$deleted) {
                return ApiResponse::error('删除失败，配置不存在');
            }

            return ApiResponse::success([], '删除成功');
        } catch (\Exception $e) {
            Log::error('删除 App 配置失败', [
                'action' => 'destroy',
                'config_id' => $configId,
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error('操作失败，请稍后重试');
        }
    }
}
