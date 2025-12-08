<?php

namespace App\Http\Resources\System;

use Illuminate\Http\Resources\Json\JsonResource;

class OperLogResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'operId' => $this->oper_id,
            'title' => $this->title,
            'businessType' => $this->business_type,
            'businessTypeName' => $this->business_type_text,
            'method' => $this->method,
            'requestMethod' => $this->request_method,
            'operatorType' => $this->operator_type,
            'operatorTypeName' => $this->operator_type_text,
            'operName' => $this->oper_name,
            'deptName' => $this->dept_name,
            'operUrl' => $this->oper_url,
            'operIp' => $this->oper_ip,
            'operLocation' => $this->oper_location,
            'operParam' => $this->oper_param,
            'jsonResult' => $this->json_result,
            'status' => $this->status,
            'errorMsg' => $this->error_msg,
            'operTime' => $this->oper_time ? $this->oper_time->format('Y-m-d H:i:s') : null,
            'costTime' => $this->cost_time,
        ];
    }
}
