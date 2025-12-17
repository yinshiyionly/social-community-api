<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicRelation;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicRelation\MaterialPolitics\CreateMaterialPoliticsRequest;
use App\Http\Requests\PublicRelation\MaterialPolitics\UpdateMaterialPoliticsRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\PublicRelation\MaterialPolitics\MaterialPoliticsItemResource;
use App\Http\Resources\PublicRelation\MaterialPolitics\MaterialPoliticsListResource;
use App\Services\PublicRelation\MaterialPoliticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 政治类资料管理控制器
 *
 * 处理政治类资料相关的HTTP请求，包括CRUD操作。
 */
class MaterialPoliticsController extends Controller
{
    /**
     * 政治类资料服务实例
     *
     * @var MaterialPoliticsService
     */
    private MaterialPoliticsService $materialPoliticsService;

    /**
     * 构造函数
     *
     * @param MaterialPoliticsService $materialPoliticsService 政治类资料管理服务
     */
    public function __construct(MaterialPoliticsService $materialPoliticsService)
    {
        $this->materialPoliticsService = $materialPoliticsService;
    }

    /**
     * 获取政治类资料列表
     *
     * GET /public-relation/material-politics
     *
     * @param Request $request 请求对象，支持以下查询参数：
     *   - keyword: 搜索关键词（真实姓名）
     *   - current: 当前页码（默认1）
     *   - size: 每页数量（默认10）
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->materialPoliticsService->getList($request->all());

        return ApiResponse::paginate($result, MaterialPoliticsListResource::class);
    }

    /**
     * 获取政治类资料详情
     *
     * GET /public-relation/material-politics/{id}
     *
     * @param int $id 政治类资料ID
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $item = $this->materialPoliticsService->getById($id);

        return ApiResponse::resource($item, MaterialPoliticsItemResource::class);
    }

    /**
     * 创建政治类资料
     *
     * POST /public-relation/material-politics
     *
     * @param CreateMaterialPoliticsRequest $request 创建政治类资料请求
     * @return JsonResponse
     */
    public function store(CreateMaterialPoliticsRequest $request): JsonResponse
    {
        $this->materialPoliticsService->create($request->validated());

        return ApiResponse::created();
    }

    /**
     * 更新政治类资料
     *
     * PUT /public-relation/material-politics/{id}
     *
     * @param UpdateMaterialPoliticsRequest $request 更新政治类资料请求
     * @param int $id 政治类资料ID
     * @return JsonResponse
     */
    public function update(UpdateMaterialPoliticsRequest $request): JsonResponse
    {
        $id = (int)$request->get('id', 0);
        $this->materialPoliticsService->update($id, $request->validated());

        return ApiResponse::updated();
    }

    /**
     * 删除政治类资料
     *
     * DELETE /public-relation/material-politics/{id}
     *
     * 执行软删除
     *
     * @param int $id 政治类资料ID
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $this->materialPoliticsService->delete($id);

        return ApiResponse::deleted();
    }
}
