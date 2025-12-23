<?php

declare(strict_types=1);

namespace App\Http\Resources\Complaint\ComplaintEnterprise;

use App\Http\Resources\BaseResource;

/**
 * 企业投诉列表资源
 *
 * 用于企业投诉列表接口的响应格式化，包含基本信息。
 * 继承 BaseResource 以支持 SoybeanAdmin 字段格式。
 */
class ComplaintEnterpriseListResource extends BaseResource
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
            'site_name' => $this->site_name,
            'account_name' => $this->account_name,
            'item_url' => $this->item_url ?? [],
            'report_state' => $this->report_state,
            'report_state_label' => $this->report_state_label,
            'created_at' => $this->formatDateTime($this->created_at),
            'completion_time' => $this->formatDateTime($this->completion_time)
        ];
    }
}
