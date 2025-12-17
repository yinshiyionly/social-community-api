<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicRelation;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicRelation\MaterialEnterprise\CreateMaterialEnterpriseRequest;
use App\Http\Requests\PublicRelation\MaterialEnterprise\UpdateMaterialEnterpriseRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\PublicRelation\MaterialEnterprise\MaterialEnterpriseItemResource;
use App\Http\Resources\PublicRelation\MaterialEnterprise\MaterialEnterpriseListResource;
use App\Services\PublicRelation\MaterialEnterpriseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 企业资料管理控制器
 *
 * 处理企业资料相关的HTTP请求，包括CRUD操作。
 */
class MaterialEnterpriseController extends Controller
{
    /**
     * 企业资料服务实例
     *
     * @var MaterialEnterpriseService
     */
    private MaterialEnterpriseService $materialEnterpriseService;

    /**
     * 构造函数
     *
     * @param MaterialEnterpriseService $materialEnterpriseService 企业资料管理服务
     */
    public function __construct(MaterialEnterpriseService $materialEnterpriseService)
    {
        $this->materialEnterpriseService = $materialEnterpriseService;
    }

    /**
     * 获取企业资料列表
     *
     * GET /public-relation/material-enterprise
     *
     * @param Request $request 请求对象，支持以下查询参数：
     *   - keyword: 搜索关键词（企业名称）
     *   - current: 当前页码（默认1）
     *   - size: 每页数量（默认10）
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->materialEnterpriseService->getList($request->all());

        return ApiResponse::paginate($result, MaterialEnterpriseListResource::class);
    }

    /**
     * 获取企业资料详情
     *
     * GET /public-relation/material-enterprise/{id}
     *
     * @param int $id 企业资料ID
     * @return JsonResponse
     * @throws ApiException
     */
    public function show(int $id): JsonResponse
    {
        $item = $this->materialEnterpriseService->getById($id);

        return ApiResponse::resource($item, MaterialEnterpriseItemResource::class);
    }

    /**
     * 创建企业资料
     *
     * POST /public-relation/material-enterprise
     *
     * @param CreateMaterialEnterpriseRequest $request 创建企业资料请求
     * @return JsonResponse
     */
    public function store(CreateMaterialEnterpriseRequest $request): JsonResponse
    {
        $this->materialEnterpriseService->create($request->validated());

        return ApiResponse::created();
    }

    /**
     * 更新企业资料
     *
     * PUT /public-relation/material-enterprise
     *
     * @param UpdateMaterialEnterpriseRequest $request 更新企业资料请求
     * @return JsonResponse
     */
    public function update(UpdateMaterialEnterpriseRequest $request): JsonResponse
    {
        $id = $request->get('id', 0);
        $this->materialEnterpriseService->update($id, $request->validated());

        return ApiResponse::updated();
    }

    /**
     * 删除企业资料
     *
     * DELETE /public-relation/material-enterprise/{id}
     *
     * 执行软删除
     *
     * @param int $id 企业资料ID
     * @return JsonResponse
     * @throws ApiException
     */
    public function destroy(int $id): JsonResponse
    {
        $this->materialEnterpriseService->delete($id);

        return ApiResponse::deleted();
    }
}
