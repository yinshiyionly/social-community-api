<?php

namespace App\Http\Resources\System;

use Illuminate\Http\Resources\Json\JsonResource;

class DictTypeResource extends JsonResource
{
    /**
     * @var mixed
     */
    public function toArray($request)
    {
        return [
            'dictId' => $this->dict_id,
            'dictName' => $this->dict_name,
            'dictType' => $this->dict_type,
            'status' => $this->status,
            'createBy' => $this->create_by,
            'createTime' => $this->create_time ? $this->create_time->format('Y-m-d H:i:s') : null,
            'updateBy' => $this->update_by,
            'updateTime' => $this->update_time ? $this->update_time->format('Y-m-d H:i:s') : null,
            'remark' => $this->remark,
        ];
    }
}
