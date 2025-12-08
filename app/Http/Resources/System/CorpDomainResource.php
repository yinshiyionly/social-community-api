<?php

namespace App\Http\Resources\System;

use Illuminate\Http\Resources\Json\JsonResource;

class CorpDomainResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'protocol' => $this->protocol,
            'protocolDisplay' => $this->protocol_display,
            'domain' => $this->domain,
            'fullUrl' => $this->full_url,
            'useType' => $this->use_type,
            'parseMethod' => $this->parse_method,
            'parseMethodText' => $this->parse_method_text,
            'use' => $this->use,
            'corpName' => $this->corp_name,
            'remark' => $this->remark,
            'status' => $this->status,
            'statusText' => $this->status_text,
            'createBy' => $this->create_by,
            'createTime' => $this->create_time ? $this->create_time->format('Y-m-d H:i:s') : null,
            'updateBy' => $this->update_by,
            'updateTime' => $this->update_time ? $this->update_time->format('Y-m-d H:i:s') : null,
        ];
    }
}