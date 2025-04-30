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
use App\Models\Common\Term;
use App\Models\SysData\Division;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Traits\Model\ModelTrait;

use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles;
    use ModelTrait;
    

    protected $guarded = [];
    public $salutations;

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
        return $this->belongsTo(Term::class, 'salutation_code', 'option_value_id');
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

    protected function personalName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->name,
            set: fn ($value) => $this->name,
        );
    }

    protected function salutationName(): Attribute
    {
        return Attribute::make(
            get: fn () => TermRepository::getCodeKeyedTermsByTaxonomyCode('Salutation')[$this->salutation_code]['name'] ?? '',
        );
    }

    protected function shippingSalutationName(): Attribute
    {
        return Attribute::make(
            get: fn () => TermRepository::getCodeKeyedTermsByTaxonomyCode('Salutation')[$this->shipping_salutation_code]['name'] ?? '',
        );
    }

    protected function shippingSalutationName2(): Attribute
    {
        return Attribute::make(
            get: fn () => TermRepository::getCodeKeyedTermsByTaxonomyCode('Salutation')[$this->shipping_salutation_code2]['name'] ?? '',
        );
    }

    protected function shippingStateName(): Attribute
    {
        return Attribute::make(
            get: function () {
                static $cities = null;
    
                if ($cities === null) {
                    $cities = Division::where('level', 1)->get()->keyBy('id');
                }
    
                return $cities[$this->shipping_state_id]->name ?? '';
            }
        );
    }

    protected function shippingCityName(): Attribute
    {
        return Attribute::make(
            get: function () {
                static $cities = null;
    
                if ($cities === null) {
                    $cities = Division::where('level', 2)->get()->keyBy('id');
                }
    
                return $cities[$this->shipping_city_id]->name ?? '';
            }
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

    public function prepareData($data)
    {
        if (strlen($data['mobile']) != 10 || !is_numeric($data['mobile']) || substr($data['mobile'], 0, 2) != '09') {
            throw new \Exception('手機號碼錯誤！');
        }

        $data['mobile'] = preg_replace('/\D+/', '', $data['mobile'] ?? null);
        $data['telephone_prefix'] = $data['telephone_prefix'] ?? null;
        $data['shipping_personal_name'] = $data['shipping_personal_name'] ?? $data['personal_name'] ?? null;
        $data['shipping_company'] = $data['shipping_company'] ?? $data['payment_company'] ?? null;
        $data['shipping_country_code'] = $data['shipping_country_code'] ?? config('vars.default_country_code');
        $data['shipping_road_abbr'] = $data['shipping_road_abbr'] ?? $data['shipping_road'] ?? null;
        $data['shipping_road'] = $data['shipping_road'] ?? null;

        return $this->processPrepareData($data);
    }
}
