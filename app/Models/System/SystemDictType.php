<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SystemDictType extends Model
{
    // use SoftDeletes;

    protected $table = 'sys_dict_type';
    protected $primaryKey = 'dict_id';
    public $timestamps = false;

    protected $fillable = [
        'dict_name', 'dict_type', 'status', 'create_by',
        'create_time', 'update_by', 'update_time', 'remark'
    ];

    protected $casts = [
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    // 关联字典数据
    public function dictData()
    {
        return $this->hasMany(SystemDictData::class, 'dict_type', 'dict_type');
    }

    // 获取正常状态的字典数据
    public function activeDictData()
    {
        return $this->dictData()->where('status', '0')->orderBy('dict_sort');
    }
}
