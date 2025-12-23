<?php

declare(strict_types=1);

namespace App\Services\Complaint;

use App\Exceptions\ApiException;
use App\Models\PublicRelation\ComplaintDefamation;
use App\Models\PublicRelation\MaterialDefamation;
use App\Services\FileUploadService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 诽谤类投诉服务类
 *
 * 负责诽谤类投诉的业务逻辑处理，包括CRUD操作、URL解析和材料字段处理
 */
class ComplaintDefamationService
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
    public const MATERIAL_FIELDS = [
        'report_material',
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
     * 获取诽谤类投诉分页列表
     *
     * 支持按 site_name/human_name/report_state 筛选
     * 按ID倒序返回分页结果
     *
     * @param array $params 查询参数 (site_name, human_name, report_state, pageNum, pageSize)
     * @return LengthAwarePaginator
     */
    public function getList(array $params): LengthAwarePaginator
    {
        $pageNum = (int)($params['pageNum'] ?? 1);
        $pageSize = (int)($params['pageSize'] ?? 10);

        return ComplaintDefamation::query()
            // 网站名称-模糊搜索
            ->when(isset($params['site_name']) && $params['site_name'] !== '', function ($q) use ($params) {
                $q->where('site_name', 'like', '%' . $params['site_name'] . '%');
            })
            // 举报人-模糊搜索
            ->when(isset($params['human_name']) && $params['human_name'] !== '', function ($q) use ($params) {
                $q->where('human_name', 'like', '%' . $params['human_name'] . '%');
            })
            // 举报状态-精确匹配
            ->when(isset($params['report_state']) && $params['report_state'] !== '', function ($q) use ($params) {
                $q->where('report_state', $params['report_state']);
            })
            ->orderBy('id', 'desc')
            ->paginate($pageSize, ['*'], 'page', $pageNum);
    }

    /**
     * 根据ID获取诽谤类投诉详情
     *
     * @param int $id 投诉记录ID
     * @return ComplaintDefamation
     * @throws ApiException 当记录不存在时抛出异常
     */
    public function getById(int $id): ComplaintDefamation
    {
        $item = ComplaintDefamation::find($id);

        if (!$item) {
            throw new ApiException('记录不存在');
        }

        return $item;
    }

    /**
     * 创建诽谤类投诉记录
     *
     * @param array $data 投诉数据
     * @return ComplaintDefamation
     * @throws ApiException 当创建失败时抛出异常
     */
    public function create(array $data): ComplaintDefamation
    {
        // 根据举报人 material_id 去 material_defamation 数据表查询资料
        $data = $this->getMaterialDataByMaterialId($data);

        // 设置默认举报类型为诽谤类
        $data['report_type'] = ComplaintDefamation::REPORT_TYPE_DEFAMATION;

        // 设置默认举报状态为平台审核中
        if (!isset($data['report_state'])) {
            $data['report_state'] = ComplaintDefamation::REPORT_STATE_PLATFORM_REVIEWING;
        }

        try {
            return ComplaintDefamation::create($data);
        } catch (\Exception $e) {
            $msg = '保存数据库失败: ' . $e->getMessage();
            Log::channel('daily')->error($msg, ['data' => $data]);
            throw new ApiException($msg);
        }
    }

    /**
     * 更新诽谤类投诉记录
     *
     * @param int $id 投诉记录ID
     * @param array $data 更新数据
     * @return bool
     * @throws ApiException 当记录不存在或更新失败时抛出异常
     */
    public function update(int $id, array $data): bool
    {
        $item = $this->getById($id);

        // 根据举报人 material_id 去 material_defamation 数据表查询资料
        $data = $this->getMaterialDataByMaterialId($data);

        try {
            return $item->update($data);
        } catch (\Exception $e) {
            $msg = '更新数据库失败: ' . $e->getMessage();
            Log::channel('daily')->error($msg, ['data' => $data]);
            throw new ApiException($msg);
        }
    }

    /**
     * 删除诽谤类投诉记录（软删除）
     *
     * @param int $id 投诉记录ID
     * @return bool
     * @throws ApiException 当记录不存在时抛出异常
     */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $item = $this->getById($id);

            return $item->delete();
        });
    }

    /**
     * 获取可用发件邮箱列表
     *
     * @return array
     */
    public function getReportEmails(): array
    {
        return DB::table('report_email')
            ->select('id', 'email', 'name')
            ->get()
            ->toArray();
    }

    /**
     * 根据 material_id 获取举报人材料并处理好
     *
     * @param array $data 包含 material_id 的数据
     * @return array 处理后的数据
     * @throws ApiException 当举报人资料不存在时抛出异常
     */
    public function getMaterialDataByMaterialId(array $data): array
    {
        $materialData = MaterialDefamation::query()
            ->select(['report_material', 'real_name'])
            ->where('id', $data['material_id'])
            ->where('status', MaterialDefamation::STATUS_ENABLED)
            ->first();

        if (empty($materialData)) {
            throw new ApiException('未找到该举报人的资料');
        }

        // 从诽谤类资料表获取举报材料
        // todo 不由 material_defamation 数据表带入, 由用户自己上传
        // $data['report_material'] = $materialData['report_material'] ?? [];

        // 设置举报人姓名（从real_name字段获取）
        $data['human_name'] = $materialData['real_name'] ?? '';

        // 处理材料字段URL（移除schema和host）
        return $this->processMaterialUrlsForStorage($data);
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
     * @param ComplaintDefamation $model 诽谤类投诉模型
     * @return ComplaintDefamation 处理后的模型
     */
    public function processMaterialUrlsForDisplay(ComplaintDefamation $model): ComplaintDefamation
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
}
