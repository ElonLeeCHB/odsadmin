<?php

namespace App\Models\Access;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Traits\HasRoles;

class SystemUser extends Model
{
    use HasFactory, HasRoles;

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
        'user_code',
        'name',
        'is_active',
        'first_access_at',
        'last_access_at',
        'access_count',
    ];

    /**
     * 屬性類型轉換
     */
    protected $casts = [
        'is_active' => 'boolean',
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

    /**
     * 取得使用者在特定系統的所有權限（合併所有角色）
     */
    public function getPermissionsForSystem(string $system)
    {
        return $this->roles
            ->flatMap->permissions
            ->unique('id')
            ->filter(fn($p) => str_starts_with($p->name, $system . '.'));
    }

    /**
     * 取得使用者的所有權限名稱（合併所有角色）
     */
    public function getAllPermissionNames()
    {
        return $this->roles
            ->flatMap->permissions
            ->pluck('name')
            ->unique();
    }
}
