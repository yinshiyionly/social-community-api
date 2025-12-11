<?php

declare(strict_types=1);

namespace App\Http\Resources\PublicRelation\MaterialEnterprise;

use App\Http\Resources\BaseResource;

/**
 * 企业资料列表资源
 *
 * 用于企业资料列表接口的响应格式化，包含基本企业信息。
 * 继承 BaseResource 以支持 SoybeanAdmin 字段格式。
 */
class MaterialEnterpriseListResource extends BaseResource
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
            'type' => $this->type,
            'nature' => $this->nature,
            'industry' => $this->industry,
            'contact_name' => $this->contact_name,
            'contact_phone' => $this->contact_phone,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'created_at' => $this->formatDateTime($this->created_at),
        ];
    }
}
