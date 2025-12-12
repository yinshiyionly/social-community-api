<?php

declare(strict_types=1);

namespace App\Http\Resources\Detection\Task;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 监测任务列表资源
 *
 * 用于监测任务列表接口的响应格式化
 */
class DetectionTaskListResource extends JsonResource
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
            'task_id' => $this->task_id,
            'task_name' => $this->task_name,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'external_task_id' => $this->external_task_id,
            'external_enable_status' => $this->external_enable_status,
            'text_plain' => $this->text_plain,
            // 'data_site' => $this->data_site,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
