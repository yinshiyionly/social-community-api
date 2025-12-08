<?php

namespace App\Http\Resources\System;

use Illuminate\Http\Resources\Json\JsonResource;

class ConfigResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'configId' => $this->config_id,
            'configName' => $this->config_name,
            'configKey' => $this->config_key,
            'configValue' => $this->config_value,
            'configType' => $this->config_type,
            'createBy' => $this->create_by,
            'createTime' => $this->create_time ? $this->create_time->format('Y-m-d H:i:s') : null,
            'updateBy' => $this->update_by,
            'updateTime' => $this->update_time ? $this->update_time->format('Y-m-d H:i:s') : null,
            'remark' => $this->remark,
        ];
    }
}
