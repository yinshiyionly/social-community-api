<?php

declare(strict_types=1);

namespace App\Http\Resources\PublicRelation\MaterialPolitics;

use App\Helper\RegionHelper;
use App\Http\Resources\BaseResource;
use App\Services\FileUploadService;

/**
 * 政治类资料详情资源
 *
 * 用于政治类资料详情接口的响应格式化，包含所有信息字段。
 * 材料字段（report_material）的URL会被处理为完整URL。
 * 继承 BaseResource 以支持 SoybeanAdmin 字段格式。
 */
class MaterialPoliticsItemResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $region = RegionHelper::getNames(
            $this->province_code,
            $this->city_code,
            $this->district_code
        );
        return [
            'id' => $this->id,
            'name' => $this->name,
            'gender' => $this->gender,
            'gender_label' => $this->gender_label,
            'contact_phone' => $this->contact_phone,
            'contact_email' => $this->contact_email,
            'province_code' => $this->province_code,
            'province' => $region['province'],
            'city_code' => $this->city_code,
            'city' => $region['city'],
            'district_code' => $this->district_code,
            'district' => $region['district'],
            'contact_address' => $this->contact_address,
            'report_material' => $this->processMaterialUrls($this->report_material),
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
