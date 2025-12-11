<?php

declare(strict_types=1);

namespace App\Http\Resources\PublicRelation\MaterialPolitics;

use App\Http\Resources\BaseResource;

/**
 * 政治类资料列表资源
 *
 * 用于政治类资料列表接口的响应格式化，包含基本信息。
 * 继承 BaseResource 以支持 SoybeanAdmin 字段格式。
 */
class MaterialDefamationListResource extends BaseResource
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
            'gender' => $this->gender,
            'gender_label' => $this->gender_label,
            'contact_phone' => $this->contact_phone,
            'contact_email' => $this->contact_email,
            'province_code' => $this->province_code,
            'city_code' => $this->city_code,
            'district_code' => $this->district_code,
            'contact_address' => $this->contact_address,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'created_at' => $this->formatDateTime($this->created_at),
        ];
    }
}

