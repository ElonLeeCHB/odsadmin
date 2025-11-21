<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\User\User;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'state_id',
        'city_id',
        'address',
        'phone',
        'manager_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * 擁有此門市訪問權限的使用者
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_stores')
                    ->withTimestamps();
    }

    /**
     * 門市店長
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * 州/省
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(\App\Models\SysData\Division::class, 'state_id');
    }

    /**
     * 城市
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(\App\Models\SysData\Division::class, 'city_id');
    }

    /**
     * 取得啟用的門市
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
