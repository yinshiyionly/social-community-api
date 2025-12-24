<?php

namespace App\Models\Mail;

use App\Models\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 实时监测任务主表模型
 *
 * @property int $id 主键ID
 * @property string $email 邮箱地址
 * @property string $auth_code 授权码
 * @property string $smtp_host SMTP 服务器地址
 * @property int $smtp_port SMTP 服务器端口
 * @property int $status 状态
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property \Illuminate\Support\Carbon|null $deleted_at 删除时间
 * @property string|null $create_by 创建人
 * @property string|null $update_by 更新人
 */
class ReportEmail extends Model
{
    use SoftDeletes;
    use HasAuditFields;

    protected $table = 'report_email';

    protected $fillable = [
        'email',
        'auth_code',
        'smtp_host',
        'smtp_port',
        'status',
        'create_by',
        'update_by',
    ];

    // 隐藏授权码
    // protected $hidden = ['auth_code'];

    protected $casts = [
        'smtp_port' => 'integer',
        'status' => 'integer'
    ];
}
