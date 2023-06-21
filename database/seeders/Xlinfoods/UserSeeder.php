<?php

namespace Database\Seeders\Xlinfoods;

use Illuminate\Database\Seeder;
use App\Models\User\User;
use App\Models\Localization\Division;
use App\Models\Localization\Road;
use Faker\Generator;
use Illuminate\Container\Container;
use App\Faker\zh_Hant\Department;
use Illuminate\Support\Arr;

class UserSeeder extends Seeder
{
    public function __construct()
    {
        $this->faker = Container::getInstance()->make(Generator::class);
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->faker->addProvider(new Department($this->faker));

        User::create([
            'code' => '23010001',  
            'email' => 'admin@xlinfoods.org',
            'password' => bcrypt('123456'),
            'name' => '王小明',
            'mobile' => '0912345678',
            'telephone' => '(02)29956656',
            'shipping_personal_name' => '王小明',
            'shipping_phone' => '(02)29956656',
            'shipping_country_code' => 'tw',
            'shipping_company' => '香臨',
            'shipping_state_id' => 3,
            'shipping_city_id' => 61,
            'shipping_road' => '光復路二段',
            'shipping_address1' => '69號',
            'is_active' => 1,
            'is_admin' => 1,
            'email_verified_at' => now(),
            ]);
    }
}
