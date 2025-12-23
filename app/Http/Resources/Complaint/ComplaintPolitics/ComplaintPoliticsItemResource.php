<?php

declare(strict_types=1);

namespace App\Http\Resources\Complaint\ComplaintPolitics;

use App\Http\Resources\BaseResource;
use App\Services\FileUploadService;

/**
 * 政治类投诉详情资源
 *
 * 用于政治类投诉详情接口的响应格式化，包含所有信息字段。
 * 材料字段（report_material）的URL会被处理为完整URL。
 * URL字段（site_url、app_url、account_url）会被处理为完整URL。
 * 继承 BaseResource 以支持日期格式化。
 */
class ComplaintPoliticsItemResource extends BaseResource
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
            'report_sub_type' => $this->report_sub_type,
            'report_platform' => $this->report_platform,

            // 网站网页相关字段
            'site_name' => $this->site_name,
            'site_url' => $this->processMaterialUrls($this->site_url),

            // APP相关字段
            'app_name' => $this->app_name,
            'app_location' => $this->app_location,
            'app_url' => $this->processMaterialUrls($this->app_url),

            // 网络账号相关字段
            'account_platform' => $this->account_platform,
            'account_nature' => $this->account_nature,
            'account_name' => $this->account_name,
            'account_platform_name' => $this->account_platform_name,
            'account_url' => $this->processMaterialUrls($this->account_url),

            // 通用字段
            'material_id' => $this->material_id,
            'human_name' => $this->human_name,
            'report_material' => $this->processMaterialUrls($this->report_material),
            'report_content' => $this->report_content,
            'send_email' => $this->send_email,
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
     * 支持两种格式：
     * 1. 材料格式：[{"name": "xxx", "url": "xxx"}]
     * 2. URL格式：[{"url": "xxx"}]
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

            $processedItem = [
                'url' => $fileUploadService->generateFileUrl($material['url']),
            ];

            // 如果有name字段，则添加（材料格式）
            if (isset($material['name'])) {
                $processedItem['name'] = $material['name'];
            }

            $processedMaterials[] = $processedItem;
        }

        return $processedMaterials;
    }
}
