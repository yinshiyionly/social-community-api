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
            // 主键ID
            'id' => $this->id,
            // 企业名称
            'name' => $this->name,
            // 企业类型
            'type' => $this->type,
            // 企业性质
            'nature' => $this->nature,
            // 行业分类
            'industry' => $this->industry,
            // 联系人身份
            'contact_identity' => $this->contact_identity,
            // 联系人姓名
            'contact_name' => $this->contact_name,
            // 联系人邮箱
            'contact_email' => $this->contact_email ?? '',
            // 联系人手机号
            'contact_phone' => $this->contact_phone,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'created_at' => $this->formatDateTime($this->created_at),
        ];
    }
}
