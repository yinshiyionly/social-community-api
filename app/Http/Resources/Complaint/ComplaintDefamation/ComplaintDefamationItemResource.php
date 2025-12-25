<?php

declare(strict_types=1);

namespace App\Http\Resources\Complaint\ComplaintDefamation;

use App\Http\Resources\BaseResource;
use App\Services\FileUploadService;

/**
 * 诽谤类投诉详情资源
 *
 * 用于诽谤类投诉详情接口的响应格式化，包含所有信息字段。
 * 材料字段（report_material）的URL会被处理为完整URL。
 * 继承 BaseResource 以支持日期格式化。
 */
class ComplaintDefamationItemResource extends BaseResource
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
            // 基本信息
            'id' => $this->id,
            'report_type' => $this->report_type,

            // 网站相关字段
            'site_name' => $this->site_name,
            'site_url' => $this->site_url,

            // 举报人相关字段
            'material_id' => $this->material_id,
            'human_name' => $this->human_name,

            // 举报材料（URL处理为完整URL）
            'report_material' => $this->processMaterialUrls($this->report_material),

            // 举报内容
            'report_content' => $this->report_content,

            // 邮箱和渠道
            'send_email' => $this->send_email,
            'email_config_id' => $this->email_config_id,
            'channel_name' => $this->channel_name,

            // 状态字段
            'report_state' => $this->report_state,
            'report_state_label' => $this->report_state_label,
            'status' => $this->status,
            'status_label' => $this->status_label,

            // 时间字段
            'report_time' => $this->formatDateTime($this->report_time),
            'completion_time' => $this->formatDateTime($this->completion_time),
            'created_at' => $this->formatDateTime($this->created_at),
            'updated_at' => $this->formatDateTime($this->updated_at),
        ];
    }

    /**
     * 处理材料字段URL（拼接完整的schema和host）
     *
     * 支持材料格式：[{"name": "xxx", "url": "xxx"}]
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
