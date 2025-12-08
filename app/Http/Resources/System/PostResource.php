<?php

namespace App\Http\Resources\System;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'postId' => $this->post_id,
            'postCode' => $this->post_code,
            'postName' => $this->post_name,
            'postSort' => $this->post_sort,
            'status' => $this->status,
            'createBy' => $this->create_by,
            'createTime' => $this->create_time ? $this->create_time->format('Y-m-d H:i:s') : null,
            'updateBy' => $this->update_by,
            'updateTime' => $this->update_time ? $this->update_time->format('Y-m-d H:i:s') : null,
            'remark' => $this->remark,
        ];
    }
}
