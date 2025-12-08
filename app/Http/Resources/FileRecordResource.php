<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FileRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'url' => $this->url,
            'file_size' => $this->file_size,
            'formatted_size' => $this->formatted_size,
            'mime_type' => $this->mime_type,
            'extension' => $this->extension,
            'storage_disk' => $this->storage_disk,
            'uploaded_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            
            // 条件包含上传者信息
            'uploader' => $this->when($this->relationLoaded('user') && $this->user, function () {
                return [
                    'id' => $this->user->user_id,
                    'name' => $this->user->nick_name ?? $this->user->user_name,
                ];
            }),
        ];
    }
}