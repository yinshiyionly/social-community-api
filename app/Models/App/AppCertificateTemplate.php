<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppCertificateTemplate extends Model
{
    use HasFactory;

    protected $table = 'app_certificate_template';
    protected $primaryKey = 'template_id';
    public $timestamps = false;

    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 2;

    protected $fillable = [
        'template_name',
        'template_image',
        'template_config',
        'status',
        'create_time',
        'update_time',
    ];

    protected $casts = [
        'template_id' => 'integer',
        'template_config' => 'array',
        'status' => 'integer',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    /**
     * 查询作用域：启用状态
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', self::STATUS_ENABLED);
    }
}
