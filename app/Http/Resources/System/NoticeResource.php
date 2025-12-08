<?php

namespace App\Http\Resources\System;

use Illuminate\Http\Resources\Json\JsonResource;

class NoticeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'noticeId' => $this->notice_id,
            'noticeTitle' => $this->notice_title,
            'noticeType' => $this->notice_type,
            'noticeContent' => $this->notice_content,
            'status' => $this->status,
            'createBy' => $this->create_by,
            'createTime' => $this->create_time ? $this->create_time->format('Y-m-d H:i:s') : null,
            'updateBy' => $this->update_by,
            'updateTime' => $this->update_time ? $this->update_time->format('Y-m-d H:i:s') : null,
            'remark' => $this->remark,
        ];
    }
}
