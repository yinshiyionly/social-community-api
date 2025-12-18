<?php

declare(strict_types=1);

namespace App\Http\Resources\PublicRelation\MaterialDefamation;

use App\Http\Resources\BaseResource;

/**
 * 诽谤类资料列表资源
 *
 */
class MaterialDefamationListResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'real_name' => $this->real_name ?? '',
            'contact_phone' => $this->contact_phone ?? '',
            'contact_email' => $this->contact_email ?? '',
            'report_subject' => $this->report_subject ?? '',
            'enterprise_name' => $this->enterprise_name ?? '-',
            'occupation_category' => $this->occupation_category ?? '',
            'status' => $this->status,
            'status_label' => $this->status_label,
            'created_at' => $this->formatDateTime($this->created_at),
        ];
    }
}

