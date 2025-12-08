<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;

class SystemNotice extends Model
{
    protected $table = 'sys_notice';
    protected $primaryKey = 'notice_id';
    public $timestamps = false;

    protected $fillable = [
        'notice_title', 'notice_type', 'notice_content', 'status',
        'create_by', 'create_time', 'update_by', 'update_time', 'remark'
    ];

    protected $casts = [
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    // 公告类型常量
    const TYPE_NOTICE = '1';  // 通知
    const TYPE_ANNOUNCEMENT = '2';  // 公告

    // 状态常量
    const STATUS_NORMAL = '0';  // 正常
    const STATUS_CLOSED = '1';  // 关闭

    /**
     * 获取公告类型文本
     */
    public function getNoticeTypeTextAttribute()
    {
        $types = [
            self::TYPE_NOTICE => '通知',
            self::TYPE_ANNOUNCEMENT => '公告'
        ];

        return $types[$this->notice_type] ?? '未知';
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute()
    {
        $statuses = [
            self::STATUS_NORMAL => '正常',
            self::STATUS_CLOSED => '关闭'
        ];

        return $statuses[$this->status] ?? '未知';
    }

    /**
     * 获取有效的公告
     */
    public static function getActiveNotices($limit = null)
    {
        $query = self::where('status', self::STATUS_NORMAL)
                    ->orderBy('create_time', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }
}
