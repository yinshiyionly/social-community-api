<?php

declare(strict_types=1);

namespace App\Http\Resources\Complaint\ComplaintPolitics;

use App\Http\Resources\BaseResource;

/**
 * 政治类投诉列表资源
 *
 * 用于政治类投诉列表接口的响应格式化，包含基本信息。
 * 继承 BaseResource 以支持日期格式化。
 */
class ComplaintPoliticsListResource extends BaseResource
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
            'report_sub_type' => $this->report_sub_type,
            'report_platform' => $this->report_platform,
            'human_name' => $this->human_name,
            'report_state' => $this->report_state,
            'report_state_label' => $this->report_state_label,
            'created_at' => $this->formatDateTime($this->created_at),
        ];
    }
}
