<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicRelation;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicRelation\MaterialDefamation\CreateMaterialDefamationRequest;
use App\Http\Requests\PublicRelation\MaterialDefamation\UpdateMaterialDefamationRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\PublicRelation\MaterialDefamation\MaterialDefamationItemResource;
use App\Http\Resources\PublicRelation\MaterialDefamation\MaterialDefamationListResource;
use App\Services\PublicRelation\MaterialDefamationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 诽谤资料管理控制器
 *
 * 处理诽谤资料相关的HTTP请求，包括CRUD操作。
 */
class MaterialDefamationController extends Controller
{
    /**
     * 诽谤资料服务实例
     *
     * @var MaterialDefamationService
     */
    private MaterialDefamationService $materialDefamationService;

    /**
     * 构造函数
     *
     * @param MaterialDefamationService $materialDefamationService 诽谤资料管理服务
     */
    public function __construct(MaterialDefamationService $materialDefamationService)
    {
        $this->materialDefamationService = $materialDefamationService;
    }

    /**
     * 获取诽谤资料列表
     *
     * GET /public-relation/material-defamation
     *
     * @param Request $request 请求对象，支持以下查询参数：
     *   - keyword: 搜索关键词（真实姓名）
     *   - current: 当前页码（默认1）
     *   - size: 每页数量（默认10）
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->materialDefamationService->getList($request->all());

        return ApiResponse::paginate($result, MaterialDefamationListResource::class);
    }

    /**
     * 获取诽谤资料详情
     *
     * GET /public-relation/material-defamation/{id}
     *
     * @param int $id 诽谤资料ID
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $item = $this->materialDefamationService->getById($id);

        return ApiResponse::resource($item, MaterialDefamationItemResource::class);
    }

    /**
     * 创建诽谤资料
     *
     * POST /public-relation/material-defamation
     *
     * @param CreateMaterialDefamationRequest $request 创建诽谤资料请求
     * @return JsonResponse
     */
    public function store(CreateMaterialDefamationRequest $request): JsonResponse
    {
        $this->materialDefamationService->create($request->validated());

        return ApiResponse::created();
    }

    /**
     * 更新诽谤资料
     *
     * PUT /public-relation/material-defamation/{id}
     *
     * @param UpdateMaterialDefamationRequest $request 更新诽谤资料请求
     * @param int $id 诽谤资料ID
     * @return JsonResponse
     */
    public function update(UpdateMaterialDefamationRequest $request, int $id): JsonResponse
    {
        $this->materialDefamationService->update($id, $request->validated());

        return ApiResponse::updated();
    }

    /**
     * 删除诽谤资料
     *
     * DELETE /public-relation/material-defamation/{id}
     *
     * 执行软删除
     *
     * @param int $id 诽谤资料ID
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $this->materialDefamationService->delete($id);

        return ApiResponse::deleted();
    }
}
