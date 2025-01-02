<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Sale\Order;
use App\Models\Catalog\OptionValueTranslation;
use App\Traits\Model\ModelTrait;

use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles;
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
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    // is_admin 必須 appends，會用在後台的 IsAdmin middleware 做判斷。
    protected $appends = ['is_admin'];

    public $meta_keys = [
    ];

    public function addresses()
    {
        return $this->hasMany(UserAddress::class, 'user_id', 'id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id', 'id');
    }

    public function salutation($locale = null)
    {
        if(empty($locale)){
            $locale = app()->getLocale();
        }
        return $this->belongsTo(OptionValueTranslation::class, 'salutation_id', 'option_value_id')
                    ->where('locale', $locale);
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

    /**
     * 其它函數 ---------------------------------------
     */

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


    public function createTokenWithExtras($name, array $abilities = ['*'], $expiresAt = null, array $extra = [])
    {
        // 創建 token
        $token = $this->createToken($name, $abilities, $expiresAt);

        // 檢查是否有 "extra" 欄位
        if (Schema::hasColumn('personal_access_tokens', 'extra')) {
            // 將附加資料儲存到資料表中
            $token->token->update([
                'extra' => json_encode($extra),
            ]);
        }

        return $token;
    }
}
