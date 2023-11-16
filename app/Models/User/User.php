<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Sale\Order;
use App\Traits\ModelTrait;

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
        'is_admin',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // is_admin 必須 appends，會用在後台的 IsAdmin middleware 做判斷。
    protected $appends = ['is_admin'];

    public $meta_attributes = [
        'is_admin',
        'first_name',
        'last_name',
        'short_name',
    ];

    public function meta_rows()
    {
        return $this->hasMany(UserMeta::class, 'user_id', 'id');
    }

    public function __get($key)
    {
        // 檢查屬性是否存在於 UserMeta 中
        $userMeta = $this->meta_rows()->where('meta_key', $key)->first();

        if ($userMeta) {
            return $userMeta->meta_value;
        }

        return parent::__get($key);
    }

    public function isAdmin():Attribute
    {
        $userMeta = $this->meta_rows()->where('meta_key', 'is_admin')->where('meta_value', '1')->first();
        
        $is_admin = ($userMeta) ? 1 : 0;

        return Attribute::make(
            get: fn ($value) => $is_admin,
        );
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id', 'id');
    }


    protected function personalName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->name,
            set: fn ($value) => $this->name,
        );
    }

    //Attribute
    protected function mobile(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->parsePhone($value),
        );
    }

    protected function telephone(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->parsePhone($value),
        );
    }

    // Mobile or Telephone
    protected function parsePhone($phone)
    {
        $phone = trim($phone);
		$phone = str_replace('-', '', $phone);
        
        $part3 = '';
        $new_phone = '';

        //Taiwan's mobile
        if(str_starts_with($phone, '09')){
            $new_phone = substr($phone, 0, 4) . '-' . substr($phone, 4) ;
        }
        // Telephone
        else{
            preg_match('/(\d+)#?(\d+)?/', $phone, $matches);

            if(!empty($matches[0])){
                $part1 = substr($matches[1],0,-4);
                $part2 = substr($matches[1],-4);
                if(!empty($matches[2])){
                    $part3 = '#' . $matches[2];
                }
                $new_phone = $part1 . '-' . $part2 . $part3;
            }else{
                $new_phone = '';
            }
        }

        return $new_phone;
    }

    // 最後一次登入
    // public function latest_login()
    // {
    //     return $this->hasOne(Login::class)->ofMany();
    // }
    // https://docfunc.com/posts/50/laravel-orm-%E7%9A%84%E6%96%B0%E5%8A%9F%E8%83%BDone-of-many-post
    //可以用這個某個產品的最新訂單
}
