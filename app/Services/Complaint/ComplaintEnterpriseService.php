<?php

declare(strict_types=1);

namespace App\Services\Complaint;

use App\Exceptions\ApiException;
use App\Models\PublicRelation\ComplaintEnterprise;
use App\Models\PublicRelation\MaterialEnterprise;
use App\Services\FileUploadService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 企业投诉服务类
 *
 * 负责企业投诉的业务逻辑处理，包括CRUD操作、URL解析和材料字段处理
 */
class ComplaintEnterpriseService
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
        'enterprise_material',
        'contact_material',
        'report_material',
        'proof_material',
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
     * 解析详细举报网址（逗号或换行分割转JSON数组）
     *
     * @param string $urls URL字符串，以逗号或换行分割
     * @return array 格式: [{"url": "xxx"}, {"url": "yyy"}]
     */
    public function parseItemUrls(string $urls): array
    {
        if (empty(trim($urls))) {
            return [];
        }

        // 将换行符统一转换为逗号，然后按逗号分割
        $urls = str_replace(["\r\n", "\r", "\n"], ',', $urls);
        $urlArray = explode(',', $urls);

        $result = [];
        foreach ($urlArray as $url) {
            $url = trim($url);
            if (!empty($url)) {
                $result[] = ['url' => $url];
            }
        }

        return $result;
    }

    /**
     * 获取企业投诉分页列表
     *
     * @param array $params 查询参数 (site_name, human_name, report_state, pageNum, pageSize)
     * @return LengthAwarePaginator
     */
    public function getList(array $params): LengthAwarePaginator
    {
        $pageNum = (int)($params['pageNum'] ?? 1);
        $pageSize = (int)($params['pageSize'] ?? 10);

        return ComplaintEnterprise::query()
            // 举报网站名称-模糊搜索
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
     * 根据ID获取企业投诉详情
     *
     * @param int $id 投诉记录ID
     * @return ComplaintEnterprise
     * @throws ApiException
     */
    public function getById(int $id): ComplaintEnterprise
    {
        $item = ComplaintEnterprise::find($id);

        if (!$item) {
            throw new ApiException('记录不存在');
        }

        return $item;
    }

    /**
     * 创建企业投诉记录
     *
     * @param array $data 投诉数据
     * @return ComplaintEnterprise
     * @throws ApiException
     */
    public function create(array $data): ComplaintEnterprise
    {
        // 根据举报人 material_id 去 material_enterprise 数据表查询资料
        $data = $this->getMaterialDataByMaterialId($data);

        // 设置默认举报类型
        $data['report_type'] = ComplaintEnterprise::REPORT_TYPE_ENTERPRISE;

        // 设置默认举报状态
        if (!isset($data['report_state'])) {
            $data['report_state'] = ComplaintEnterprise::REPORT_STATE_PLATFORM_REVIEWING;
        }
        try {
            return ComplaintEnterprise::create($data);
        } catch (\Exception $e) {
            $msg = '保存数据库失败: ' . $e->getMessage();
            Log::channel('daily')->error($msg, ['data' => $data]);
            throw new ApiException($msg);
        }
    }

    /**
     * 更新企业投诉记录
     *
     * @param int $id 投诉记录ID
     * @param array $data 更新数据
     * @return bool
     * @throws ApiException
     */
    public function update(int $id, array $data): bool
    {
        $item = $this->getById($id);

        // 根据举报人 material_id 去 material_enterprise 数据表查询资料
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
     * 删除企业投诉记录（软删除）
     *
     * @param int $id 投诉记录ID
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
     * @param ComplaintEnterprise $model 企业投诉模型
     * @return ComplaintEnterprise 处理后的模型
     */
    public function processMaterialUrlsForDisplay(ComplaintEnterprise $model): ComplaintEnterprise
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
     * 获取证据种类枚举列表
     *
     * @return array
     */
    public function getProofTypes(): array
    {
        return ComplaintEnterprise::PROOF_TYPE_OPTIONS;
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
     * @param array $data
     * @return array
     * @throws ApiException
     */
    public function getMaterialDataByMaterialId(array $data): array
    {
        $materialData = MaterialEnterprise::query()
            ->select(['enterprise_material', 'contact_material', 'report_material', 'proof_material', 'contact_name'])
            ->where('id', $data['material_id'])
            ->where('status', MaterialEnterprise::STATUS_ENABLED)
            ->first();
        if (empty($materialData)) {
            throw new ApiException('未找到该举报人的资料');
        }
        $data['enterprise_material'] = $materialData['enterprise_material'] ?? [];
        $data['contact_material'] = $materialData['contact_material'] ?? [];
        $data['report_material'] = $materialData['report_material'] ?? [];
        $data['proof_material'] = $materialData['proof_material'] ?? [];
        $data['human_name'] = $materialData['contact_name'] ?? '';

        // 处理材料字段URL（移除schema和host）
        return $this->processMaterialUrlsForStorage($data);
    }
}
