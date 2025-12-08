<?php

namespace App\Http\Resources\System;

use Illuminate\Http\Resources\Json\JsonResource;

class LoginInforResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'infoId' => $this->info_id,
            'userName' => $this->user_name,
            'ipaddr' => $this->ipaddr,
            'loginLocation' => $this->login_location,
            'browser' => $this->browser,
            'os' => $this->os,
            'status' => $this->status,
            'statusName' => $this->status_text,
            'msg' => $this->msg,
            'loginTime' => $this->login_time ? $this->login_time->format('Y-m-d H:i:s') : null,
        ];
    }
}
