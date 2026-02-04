<?php

namespace App\Models\App;

use App\Models\Traits\HasOperator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppCertificateTemplate extends Model
{
    use HasFactory, SoftDeletes, HasOperator;

    protected $table = 'app_certificate_template';
    protected $primaryKey = 'template_id';

    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 2;

    protected $fillable = [
        'template_name',
        'template_image',
        'template_config',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'template_id' => 'integer',
        'template_config' => 'array',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 查询作用域：启用状态
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', self::STATUS_ENABLED);
    }
}
