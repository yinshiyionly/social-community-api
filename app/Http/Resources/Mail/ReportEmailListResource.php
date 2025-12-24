<?php

namespace App\Http\Resources\Mail;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 举报邮箱API资源
 */
class ReportEmailListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id ?? 0,
            'email' => $this->email ?? '',
            'auth_code' => $this->auth_code ?? '',
            'smtp_host' => $this->smtp_host ?? '',
            'smtp_port' => $this->smtp_port ?? 465,
            'status' => $this->status,
            'status_text' => $this->status ? '启用' : '禁用',
            // 'create_by' => $this->create_by,
            // 'update_by' => $this->update_by,
            // 'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            // 'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
