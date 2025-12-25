<?php

declare(strict_types=1);

namespace App\Http\Resources\Complaint\ComplaintDefamation;

use App\Http\Resources\BaseResource;

/**
 * 诽谤类投诉列表资源
 *
 * 用于诽谤类投诉列表接口的响应格式化，包含基本信息。
 * 继承 BaseResource 以支持 SoybeanAdmin 字段格式。
 */
class ComplaintDefamationListResource extends BaseResource
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
            'site_name' => $this->site_name ?? '',
            'site_url' => $this->site_url ?? [],
            'email_config_id' => $this->email_config_id,
            'report_state' => $this->report_state,
            'report_state_label' => $this->report_state_label,
            'created_at' => $this->formatDateTime($this->created_at),
            'completion_time' => $this->formatDateTime($this->completion_time),
        ];
    }
}
