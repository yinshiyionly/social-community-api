<?php

declare(strict_types=1);

namespace App\Services\PublicRelation;

use App\Exceptions\ApiException;
use App\Models\PublicRelation\MaterialEnterprise;
use App\Services\FileUploadService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * 企业资料管理服务
 *
 * 负责企业资料的业务逻辑处理，包括CRUD操作和材料字段URL处理
 */
class MaterialEnterpriseService
{
    /**
     * 文件上传服务实例
     *
     * @var FileUploadService
     */
    private FileUploadService $fileUploadService;

    /**
     * 材料字段名称列表
     *
     * @var array
     */
    private const MATERIAL_FIELDS = [
        'enterprise_material',
        'report_material',
        'proof_material',
        'contact_material'
    ];

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
     * 获取企业资料分页列表
     *
     * @param array $params 查询参数 (keyword, current, size)
     * @return LengthAwarePaginator
     */
    public function getList(array $params): LengthAwarePaginator
    {
        $pageNum = (int)($params['pageNum'] ?? 1);
        $pageSize = (int)($params['pageSize'] ?? 10);

        return MaterialEnterprise::query()
            // 企业名称-模糊搜索
            ->when(isset($params['name']) && $params['name'] != '', function ($q) use ($params) {
                $q->where('name', 'like', '%' . $params['name'] . '%');
            })
            // 联系人姓名-模糊搜索
            ->when(isset($params['contact_name']) && $params['contact_name'] != '', function ($q) use ($params) {
                $q->where('contact_name', 'like', '%' . $params['contact_name'] . '%');
            })
            ->orderBy('id', 'desc')
            ->paginate($pageSize, ['*'], 'page', $pageNum);
    }

    /**
     * 根据ID获取企业资料详情
     *
     * @param int $id 企业资料ID
     * @return MaterialEnterprise
     * @throws ApiException
     */
    public function getById(int $id): MaterialEnterprise
    {
        $item = MaterialEnterprise::find($id);

        if (!$item) {
            throw new ApiException('记录不存在');
        }

        return $item;
    }


    /**
     * 创建企业资料
     *
     * @param array $data 企业资料数据
     * @return MaterialEnterprise
     */
    public function create(array $data): MaterialEnterprise
    {
        return DB::transaction(function () use ($data) {
            // 处理材料字段URL（移除schema和host）
            $data = $this->processMaterialUrlsForStorage($data);

            return MaterialEnterprise::create($data);
        });
    }

    /**
     * 更新企业资料
     *
     * @param int $id 企业资料ID
     * @param array $data 更新数据
     * @return bool
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
     * 删除企业资料（软删除）
     *
     * @param int $id 企业资料ID
     * @return bool
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
        foreach (self::MATERIAL_FIELDS as $field) {
            if (!isset($data[$field]) || !is_array($data[$field])) {
                continue;
            }

            $materials = [];
            foreach ($data[$field] as $material) {
                if (!is_array($material) || !isset($material['url'])) {
                    continue;
                }

                $materials[] = [
                    'name' => $material['name'] ?? '',
                    'url' => $this->fileUploadService->extractPathFromUrl($material['url']),
                ];
            }

            $data[$field] = $materials;
        }

        return $data;
    }

    /**
     * 处理材料字段URL用于展示（拼接完整的schema和host）
     *
     * @param MaterialEnterprise $model 企业资料模型
     * @return MaterialEnterprise 处理后的模型
     */
    public function processMaterialUrlsForDisplay(MaterialEnterprise $model): MaterialEnterprise
    {
        foreach (self::MATERIAL_FIELDS as $field) {
            $materials = $model->{$field};

            if (empty($materials) || !is_array($materials)) {
                $model->{$field} = [];
                continue;
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

            $model->{$field} = $processedMaterials;
        }

        return $model;
    }

    /**
     * 获取举报人实体列表
     *
     * @return \Illuminate\Support\Collection
     */
    public function getReportEntityList()
    {
        return MaterialEnterprise::query()
            ->where('status', MaterialEnterprise::STATUS_ENABLED)
            ->pluck('contact_name')
            ->map(function ($label) {
                return [
                    'label' => $label,
                    'value' => $label
                ];
            })
            ->values(); // 重新索引数组
    }
}
