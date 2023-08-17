<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\Model\ModelTrait;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use ModelTrait;
    
    protected $guarded = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    // protected $fillable = [
    //     'name',
    //     'email',
    //     'password',
    // ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    //protected $appends = ['user_meta'];

    public $meta_keys = [
    ];

    protected static function booted()
    {
        parent::booted();

        static::addGlobalScope(function ($query) {
            $query->with('userMeta');
        });
    }

    public function userMeta()
    {
        return $this->hasMany(UserMeta::class, 'user_id', 'id');
    }

    public function __get($key)
    {
        // 檢查屬性是否存在於 UserMeta 中
        $userMeta = $this->userMeta()->where('meta_key', $key)->first();

        if ($userMeta) {
            return $userMeta->meta_value;
        }

        return parent::__get($key);
    }

    // public function isAdmin()
    // {
    //     $userMeta = $this->userMeta()->where('meta_key', 'is_admin')->where('meta_value', '1')->first();

    //     $is_admin = 0;

    //     if($userMeta){
    //         $is_admin = $userMeta->meta_value;
    //     }

    //     return Attribute::make(
    //         get: fn ($value) => $is_admin,
    //     );
    // }

    protected function firstName(): Attribute
    {
        if(!empty($this->attributes['firstName'])){
            $value = ucfirst($value);
        }

        return Attribute::make(
            get: fn ($value) => $value,
        );
    }

    protected function lastName(): Attribute
    {
        if(!empty($this->attributes['firstName'])){
            $value = ucfirst($value);
        }

        return Attribute::make(
            get: fn ($value) => $value,
        );
    }

    protected function personalName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ucfirst($this->name),
            set: fn ($value) => ucfirst($this->name),
        );
    }

    // 最後一次登入
    // public function latest_login()
    // {
    //     return $this->hasOne(Login::class)->ofMany();
    // }
    // https://docfunc.com/posts/50/laravel-orm-%E7%9A%84%E6%96%B0%E5%8A%9F%E8%83%BDone-of-many-post
    //可以用這個某個產品的最新訂單
}
