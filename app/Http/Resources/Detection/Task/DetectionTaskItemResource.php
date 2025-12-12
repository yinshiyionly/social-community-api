<?php

declare(strict_types=1);

namespace App\Http\Resources\Detection\Task;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 监测任务详情资源
 *
 * 用于监测任务详情接口的响应格式化
 */
class DetectionTaskItemResource extends JsonResource
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
            // 基础信息
            'task_id' => $this->task_id,
            'task_name' => $this->task_name,
            'status' => $this->status,
            'status_label' => $this->status_label,

            // 外部任务信息
            'external_task_id' => $this->external_task_id,
            'external_enable_status' => $this->external_enable_status,
            // 'external_sync_mode' => $this->external_sync_mode,

            // 规则配置
            'text_plain' => $this->text_plain,
            // 'text_rule' => $this->text_rule,
            'tag_plain' => $this->tag_plain,
            // 'tag_rule' => $this->tag_rule,
            'based_location_plain' => $this->based_location_plain,
            // 'based_location_rule' => $this->based_location_rule,
            // 'data_site' => $this->data_site,

            // 预警配置
            'warn_name' => $this->warn_name,
            'warn_reception_start_time' => $this->warn_reception_start_time->format('H:i'),
            'warn_reception_end_time' => $this->warn_reception_end_time->format('H:i'),
            'warn_publish_email_state' => $this->warn_publish_email_state,
            'warn_publish_email_config' => $this->warn_publish_email_config,
            'warn_publish_wx_state' => $this->warn_publish_wx_state,
            'warn_publish_wx_config' => $this->warn_publish_wx_config,

            // 时间信息
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
