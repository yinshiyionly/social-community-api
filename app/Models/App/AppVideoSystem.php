<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppVideoSystem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_video_system';
    protected $primaryKey = 'video_id';

    const STATUS_ENABLED = 1;  // 启用
    const STATUS_DISABLED = 2; // 禁用

    protected $fillable = [
        'name',
        'status',
        'total_size',
        'preface_url',
        'play_url',
        'length',
        'width',
        'height',
    ];

    protected $casts = [
        'video_id' => 'integer',
        'status' => 'integer',
        'length' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 启用状态
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', self::STATUS_ENABLED);
    }

    /**
     * 是否启用
     */
    public function isEnabled(): bool
    {
        return $this->status === self::STATUS_ENABLED;
    }
}
