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

        ];
    }
}
