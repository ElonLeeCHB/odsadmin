<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;

class OrderGroup extends Model
{
    protected $fillable = [
        'notes',
        'creator_id',
        'modifier_id',
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'order_group_id');
    }
}
