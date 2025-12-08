<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;

class SystemOperLog extends Model
{
    protected $table = 'sys_oper_log';
    protected $primaryKey = 'oper_id';
    public $timestamps = false;

    protected $fillable = [
        'title',
        'event_type',
        'route',
        'request_method',
        'user_agent',
        'operator_id',
        'oper_name',
        'oper_url',
        'oper_ip',
        'oper_location',
        'oper_param',
        'event_time'
    ];

    protected $casts = [
        'event_time' => 'datetime',
        'operator_id' => 'integer',
        'event_type' => 'integer',
    ];

    // 业务类型常量（event_type）
    const EVENT_OTHER = 0;    // 其它
    const EVENT_INSERT = 1;   // 新增
    const EVENT_UPDATE = 2;   // 修改
    const EVENT_DELETE = 3;   // 删除
    const EVENT_EXPORT = 4;   // 导出
    const EVENT_IMPORT = 5;   // 导入
    const EVENT_GRANT = 6;    // 授权
    const EVENT_QUERY = 7;    // 查询

    // 保留旧常量以兼容
    const BUSINESS_OTHER = 0;
    const BUSINESS_INSERT = 1;
    const BUSINESS_UPDATE = 2;
    const BUSINESS_DELETE = 3;
    const BUSINESS_GRANT = 6;
    const BUSINESS_EXPORT = 4;
    const BUSINESS_IMPORT = 5;

    /**
     * 获取事件类型文本
     */
    public function getEventTypeTextAttribute()
    {
        $types = [
            self::EVENT_OTHER => '其它',
            self::EVENT_INSERT => '新增',
            self::EVENT_UPDATE => '修改',
            self::EVENT_DELETE => '删除',
            self::EVENT_EXPORT => '导出',
            self::EVENT_IMPORT => '导入',
            self::EVENT_GRANT => '授权',
            self::EVENT_QUERY => '查询'
        ];

        return $types[$this->event_type] ?? '未知';
    }

    /**
     * 获取业务类型文本（兼容旧方法）
     */
    public function getBusinessTypeTextAttribute()
    {
        return $this->getEventTypeTextAttribute();
    }

    /**
     * 关联操作用户
     */
    public function operator()
    {
        return $this->belongsTo(SystemUser::class, 'operator_id', 'user_id');
    }

    /**
     * 记录操作日志
     */
    public static function record(array $data)
    {
        return self::create(array_merge([
            'event_time' => now(),
            'event_type' => self::EVENT_OTHER
        ], $data));
    }

    /**
     * 清理过期日志
     */
    public static function cleanExpiredLogs($days = 30)
    {
        $expiredDate = now()->subDays($days);
        return self::where('event_time', '<', $expiredDate)->delete();
    }
}
