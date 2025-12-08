<?php

namespace App\Http\Resources\System;

use Illuminate\Http\Resources\Json\JsonResource;

class DictDataResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'dictCode' => $this->dict_code,
            'dictSort' => $this->dict_sort,
            'dictLabel' => $this->dict_label,
            'dictValue' => $this->dict_value,
            'dictType' => $this->dict_type,
            'cssClass' => $this->css_class,
            'listClass' => $this->list_class,
            'isDefault' => $this->is_default,
            'status' => $this->status,
            'createBy' => $this->create_by,
            'createTime' => $this->create_time ? $this->create_time->format('Y-m-d H:i:s') : null,
            'updateBy' => $this->update_by,
            'updateTime' => $this->update_time ? $this->update_time->format('Y-m-d H:i:s') : null,
            'remark' => $this->remark,
        ];
    }
}
