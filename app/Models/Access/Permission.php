<?php

namespace App\Models\Access;

use Spatie\Permission\Models\Permission as SpatiePermission;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Permission extends SpatiePermission
{
    protected $table = 'permissions';

    protected $fillable = [
        'parent_id',
        'name',
        'guard_name',
        'title',
        'description',
        'icon',
        'sort_order',
        'type',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * 父層權限
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'parent_id');
    }

    /**
     * 子層權限
     */
    public function children(): HasMany
    {
        return $this->hasMany(Permission::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * 取得所有子孫權限（遞迴）
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * 取得所有祖先權限（遞迴）
     */
    public function ancestors()
    {
        $ancestors = collect();
        $parent = $this->parent;

        while ($parent) {
            $ancestors->push($parent);
            $parent = $parent->parent;
        }

        return $ancestors;
    }

    /**
     * 檢查所有祖先是否都有權限
     */
    public function hasAllAncestorPermissions($userPermissions): bool
    {
        foreach ($this->ancestors() as $ancestor) {
            if (!$userPermissions->contains($ancestor->name)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 是否為選單項目
     */
    public function isMenu(): bool
    {
        return $this->type === 'menu';
    }

    /**
     * 是否為功能權限
     */
    public function isAction(): bool
    {
        return $this->type === 'action';
    }

    /**
     * 檢查是否為頂層權限
     */
    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * 取得權限層級（深度）
     */
    public function getLevel(): int
    {
        return $this->ancestors()->count();
    }

    /**
     * 取得系統前綴（pos, admin, www）
     */
    public function getSystem(): ?string
    {
        $parts = explode('.', $this->name);
        return $parts[0] ?? null;
    }

    /**
     * 檢查是否屬於某系統
     */
    public function belongsToSystem(string $system): bool
    {
        return str_starts_with($this->name, $system . '.');
    }

    /**
     * Scope: 只取得選單項目
     */
    public function scopeMenuOnly($query)
    {
        return $query->where('type', 'menu');
    }

    /**
     * Scope: 只取得功能權限
     */
    public function scopeActionOnly($query)
    {
        return $query->where('type', 'action');
    }

    /**
     * Scope: 只取得頂層權限
     */
    public function scopeRootOnly($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: 只取得特定系統的權限
     */
    public function scopeSystem($query, string $system)
    {
        return $query->where('name', 'like', $system . '.%');
    }
}
