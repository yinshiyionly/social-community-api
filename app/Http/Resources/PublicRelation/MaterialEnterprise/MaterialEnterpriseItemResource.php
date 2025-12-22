<?php

declare(strict_types=1);

namespace App\Http\Resources\PublicRelation\MaterialEnterprise;

use App\Http\Resources\BaseResource;
use App\Services\FileUploadService;

/**
 * 企业资料详情资源
 *
 * 用于企业资料详情接口的响应格式化，包含所有企业信息字段。
 * 材料字段（enterprise_material、report_material、proof_material）的URL会被处理为完整URL。
 * 继承 BaseResource 以支持 SoybeanAdmin 字段格式。
 */
class MaterialEnterpriseItemResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'enterprise_material' => $this->processMaterialUrls($this->enterprise_material),
            'type' => $this->type,
            'nature' => $this->nature,
            'industry' => $this->industry,
            'contact_identity' => $this->contact_identity,
            'contact_material' => $this->processMaterialUrls($this->contact_material),
            'contact_name' => $this->contact_name,
            'contact_phone' => $this->contact_phone,
            'contact_email' => $this->contact_email,
            // 'report_material' => $this->processMaterialUrls($this->report_material),
            // 'proof_material' => $this->processMaterialUrls($this->proof_material),
            'status' => $this->status,
            'status_label' => $this->status_label,
            'created_at' => $this->formatDateTime($this->created_at),
            'updated_at' => $this->formatDateTime($this->updated_at),
        ];
    }

    /**
     * 处理材料字段URL（拼接完整的schema和host）
     *
     * @param array|null $materials 材料数组
     * @return array 处理后的材料数组
     */
    private function processMaterialUrls(?array $materials): array
    {
        if (empty($materials) || !is_array($materials)) {
            return [];
        }

        /** @var FileUploadService $fileUploadService */
        $fileUploadService = app(FileUploadService::class);

        $processedMaterials = [];
        foreach ($materials as $material) {
            if (!is_array($material) || !isset($material['url'])) {
                continue;
            }

            $processedMaterials[] = [
                'name' => $material['name'] ?? '',
                'url' => $fileUploadService->generateFileUrl($material['url']),
            ];
        }

        return $processedMaterials;
    }
}
