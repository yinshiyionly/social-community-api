<?php

declare(strict_types=1);

namespace App\Http\Resources\Complaint\ComplaintEnterprise;

use App\Http\Resources\BaseResource;
use App\Services\FileUploadService;

/**
 * 企业投诉详情资源
 *
 * 用于企业投诉详情接口的响应格式化，包含所有信息字段。
 * 材料字段（enterprise_material, contact_material, report_material, proof_material）的URL会被处理为完整URL。
 * 继承 BaseResource 以支持 SoybeanAdmin 字段格式。
 */
class ComplaintEnterpriseItemResource extends BaseResource
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
            'report_type' => $this->report_type,
            'material_id' => $this->material_id,
            'human_name' => $this->human_name,
            'enterprise_material' => $this->processMaterialUrls($this->enterprise_material),
            'contact_material' => $this->processMaterialUrls($this->contact_material),
            'report_material' => $this->processMaterialUrls($this->report_material),
            'proof_material' => $this->processMaterialUrls($this->proof_material),
            'site_name' => $this->site_name,
            'account_name' => $this->account_name,
            'item_url' => $this->item_url,
            'report_content' => $this->report_content,
            'proof_type' => $this->proof_type,
            'proof_type_label' => $this->proof_type_label,
            'send_email' => $this->send_email,
            'email_config_id' => $this->email_config_id ?? 0,
            'channel_name' => $this->channel_name,
            'report_state' => $this->report_state,
            'report_state_label' => $this->report_state_label,
            'report_time' => $this->formatDateTime($this->report_time),
            'completion_time' => $this->formatDateTime($this->completion_time),
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
