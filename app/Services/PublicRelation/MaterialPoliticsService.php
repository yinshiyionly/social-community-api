<?php

declare(strict_types=1);

namespace App\Services\PublicRelation;

use App\Exceptions\ApiException;
use App\Models\PublicRelation\MaterialPolitics;
use App\Services\FileUploadService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * 政治类资料管理服务
 *
 * 负责政治类资料的业务逻辑处理，包括CRUD操作和材料字段URL处理
 */
class MaterialPoliticsService
{
    /**
     * 文件上传服务实例
     *
     * @var FileUploadService
     */
    private FileUploadService $fileUploadService;

    /**
     * 材料字段名称
     *
     * @var string
     */
    private const MATERIAL_FIELD = 'report_material';

    /**
     * 构造函数
     *
     * @param FileUploadService $fileUploadService 文件上传服务
     */
    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * 获取政治类资料分页列表
     *
     * @param array $params 查询参数 (keyword, current, size)
     * @return LengthAwarePaginator
     */
    public function getList(array $params): LengthAwarePaginator
    {
        $pageNum = (int)($params['pageNum'] ?? 1);
        $pageSize = (int)($params['pageSize'] ?? 10);

        return MaterialPolitics::query()
            // 姓名-模糊搜索
            ->when(isset($params['name']) && $params['name'] !== '', function ($query) use ($params) {
                $query->where('name', 'like', '%' . $params['name'] . '%');
            })
            ->orderBy('id', 'desc')
            ->paginate($pageSize, ['*'], 'page', $pageNum);
    }

    /**
     * 根据ID获取政治类资料详情
     *
     * @param int $id 政治类资料ID
     * @return MaterialPolitics
     * @throws ApiException
     */
    public function getById(int $id): MaterialPolitics
    {
        $item = MaterialPolitics::find($id);

        if (!$item) {
            throw new ApiException('记录不存在');
        }

        return $item;
    }

    /**
     * 创建政治类资料
     *
     * @param array $data 政治类资料数据
     * @return MaterialPolitics
     */
    public function create(array $data): MaterialPolitics
    {
        return DB::transaction(function () use ($data) {
            // 处理材料字段URL（移除schema和host）
            $data = $this->processMaterialUrlsForStorage($data);

            return MaterialPolitics::create($data);
        });
    }

    /**
     * 更新政治类资料
     *
     * @param int $id 政治类资料ID
     * @param array $data 更新数据
     * @return bool
     * @throws ApiException
     */
    public function update(int $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data) {
            $item = $this->getById($id);

            // 处理材料字段URL（移除schema和host）
            $data = $this->processMaterialUrlsForStorage($data);

            return $item->update($data);
        });
    }

    /**
     * 删除政治类资料（软删除）
     *
     * @param int $id 政治类资料ID
     * @return bool
     * @throws ApiException
     */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $item = $this->getById($id);

            return $item->delete();
        });
    }

    /**
     * 处理材料字段URL用于存储（移除schema和host，只保留path）
     *
     * @param array $data 包含材料字段的数据
     * @return array 处理后的数据
     */
    public function processMaterialUrlsForStorage(array $data): array
    {
        if (!isset($data[self::MATERIAL_FIELD]) || !is_array($data[self::MATERIAL_FIELD])) {
            return $data;
        }

        $materials = [];
        foreach ($data[self::MATERIAL_FIELD] as $material) {
            if (!is_array($material) || !isset($material['url'])) {
                continue;
            }

            $materials[] = [
                'name' => $material['name'] ?? '',
                'url' => $this->fileUploadService->extractPathFromUrl($material['url']),
            ];
        }

        $data[self::MATERIAL_FIELD] = $materials;

        return $data;
    }

    /**
     * 处理材料字段URL用于展示（拼接完整的schema和host）
     *
     * @param MaterialPolitics $model 政治类资料模型
     * @return MaterialPolitics 处理后的模型
     */
    public function processMaterialUrlsForDisplay(MaterialPolitics $model): MaterialPolitics
    {
        $materials = $model->{self::MATERIAL_FIELD};

        if (empty($materials) || !is_array($materials)) {
            $model->{self::MATERIAL_FIELD} = [];
            return $model;
        }

        $processedMaterials = [];
        foreach ($materials as $material) {
            if (!is_array($material) || !isset($material['url'])) {
                continue;
            }

            $processedMaterials[] = [
                'name' => $material['name'] ?? '',
                'url' => $this->fileUploadService->generateFileUrl($material['url']),
            ];
        }

        $model->{self::MATERIAL_FIELD} = $processedMaterials;

        return $model;
    }

    /**
     * 获取举报人实体列表
     *
     * @return \Illuminate\Support\Collection
     */
    public function getReportEntityList()
    {
        return MaterialPolitics::query()
            ->where('status', MaterialPolitics::STATUS_ENABLED)
            ->pluck('name')
            ->map(function ($label) {
                return [
                    'label' => $label,
                    'value' => $label
                ];
            })
            ->values(); // 重新索引数组
    }
}
