<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    /**
     * 使用 sysdata 資料庫連線
     */
    protected $connection = 'sysdata';

    protected $table = 'logs';

    /**
     * 停用 updated_at，日誌只需要 created_at
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'request_trace_id',
        'area',
        'url',
        'method',
        'data',
        'status',
        'note',
        'client_ip',
        'api_ip',
    ];

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
    ];
}
