<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemUser extends Model
{
    use HasFactory;

    /**
     * 對應的資料表
     */
    protected $table = 'system_users';

    /**
     * 主鍵為 user_id
     */
    protected $primaryKey = 'user_id';

    /**
     * 主鍵不是自動遞增
     */
    public $incrementing = false;

    /**
     * 可批量賦值的屬性
     */
    protected $fillable = [
        'user_id',
        'first_access_at',
        'last_access_at',
        'access_count',
    ];

    /**
     * 屬性類型轉換
     */
    protected $casts = [
        'first_access_at' => 'datetime',
        'last_access_at' => 'datetime',
        'access_count' => 'integer',
    ];

    /**
     * 關聯到 User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User\User::class, 'user_id');
    }
}
