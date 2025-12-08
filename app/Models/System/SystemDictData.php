<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;

class SystemDictData extends Model
{
    protected $table = 'sys_dict_data';
    protected $primaryKey = 'dict_code';
    public $timestamps = false;

    protected $fillable = [
        'dict_sort', 'dict_label', 'dict_value', 'dict_type',
        'css_class', 'list_class', 'is_default', 'status',
        'create_by', 'create_time', 'update_by', 'update_time', 'remark'
    ];

    protected $casts = [
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    // 关联字典类型
    public function dictType()
    {
        return $this->belongsTo(SystemDictType::class, 'dict_type', 'dict_type');
    }

    // 获取字典标签样式类
    public function getListClassAttribute($value)
    {
        if (empty($value)) {
            return 'default';
        }
        return $value;
    }
}
