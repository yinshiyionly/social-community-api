<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Base API Resource for SoybeanAdmin format.
 *
 * Provides common helper methods for field transformation:
 * - Audit fields (createBy, createTime, updateBy, updateTime)
 * - DateTime formatting (Y-m-d H:i:s)
 * - Status transformation (1→"1", 0→"2")
 */
abstract class BaseResource extends JsonResource
{
    /**
     * Get common audit fields for SoybeanAdmin format.
     *
     * Transforms snake_case database fields to camelCase:
     * - create_by → createBy
     * - created_at → createTime
     * - update_by → updateBy
     * - updated_at → updateTime
     *
     * @return array
     */
    protected function getAuditFields(): array
    {
        return [
            'createBy' => $this->create_by ?? null,
            'createTime' => $this->formatDateTime($this->created_at),
            'updateBy' => $this->update_by ?? null,
            'updateTime' => $this->formatDateTime($this->updated_at),
        ];
    }

    /**
     * Format datetime to SoybeanAdmin expected format.
     *
     * @param mixed $datetime Carbon instance, string, or null
     * @return string|null Formatted as "Y-m-d H:i:s" or null
     */
    protected function formatDateTime($datetime): ?string
    {
        if ($datetime === null) {
            return null;
        }

        if ($datetime instanceof Carbon) {
            return $datetime->format('Y-m-d H:i:s');
        }

        // Handle string datetime values
        if (is_string($datetime)) {
            try {
                return Carbon::parse($datetime)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                return $datetime;
            }
        }

        return null;
    }

    /**
     * Transform status to SoybeanAdmin string format.
     *
     * Database values: 1=enabled, 0=disabled
     * SoybeanAdmin values: "1"=enabled, "2"=disabled
     *
     * @param mixed $status Integer or string status value
     * @return string|null "1" for enabled, "2" for disabled, null if input is null
     */
    protected function transformStatus($status): ?string
    {
        if ($status === null) {
            return null;
        }

        // Convert to integer for comparison (handles both int and string inputs)
        $statusInt = (int) $status;

        return $statusInt === 1 ? '1' : '2';
    }
}
