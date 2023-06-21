<?php

namespace Database\Seeders;

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
    private $faker;
    
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

        $road_id = rand(1,50000);
        $road = Road::find($road_id)->name;

        $fakerAddress = $this->faker->address();

        preg_match("/(\d+巷.*)/", $fakerAddress,$matches);
        if(!empty($matches[1])){
            $address1 = $matches[1];
        }

        if(empty($address1)){
            preg_match("/(\d+號.*)/", $fakerAddress,$matches);
            if(!empty($matches[1])){
                $address1 = $matches[1];
            }
        }

        if(empty($address1)){
            $address1 = $fakerAddress;
        }

        User::create([
            'code' => '22090001',
            'username' => 'admin',
            'first_name' => '',
            'last_name' => '',
            'short_name' => 'Short',   
            'email' => 'admin@example.org',
            'password' => bcrypt('123456'),
            'name' => 'Administrator',
            'mobile' => $this->faker->phoneNumber(),

            'shipping_personal_name' => $this->faker->lastName() . $this->faker->firstName(),
            'shipping_phone' => $this->faker->phoneNumber(),
            'shipping_country_code' => 'tw',
            'shipping_company' => $this->faker->company(),
            'shipping_state_id' => 3,
            'shipping_city_id' => 44,
            'shipping_road' => '中正路',
            'shipping_address1' => $address1,

            'is_active' => 1,
            'is_admin' => 1,
            'email_verified_at' => now(),
            ]);
            
        
        for ($i=1; $i < 201; $i++) {

            $state_id = rand(1,22);
            $ids = Division::where('parent_id', $state_id)->get()->pluck('id')->toArray();
            $city_id = Arr::random($ids,1)[0];
            
            $road_id = rand(1,50000);
            $road = Road::find($road_id)->name;

            $fakerAddress = $this->faker->address();

            preg_match("/(\d+巷.*)/", $fakerAddress,$matches);
            if(!empty($matches[1])){
                $address1 = $matches[1];
            }

            if(empty($address1)){
                preg_match("/(\d+號.*)/", $fakerAddress,$matches);
                if(!empty($matches[1])){
                    $address1 = $matches[1];
                }
            }

            if(empty($address1)){
                $address1 = $fakerAddress;
            }

            $mobile = $this->faker->phoneNumber();

            $name = $this->faker->lastName() . $this->faker->firstName();

            User::create([
                'code' => '2210'.sprintf("%04d",$i),
                'name' => $name,
                //'first_name' => $this->faker->firstName(),
                //'last_name' => $this->faker->lastName(),
                'short_name' => $this->faker->firstName(),        
                'email' => $this->faker->unique()->safeEmail(),
                'email_verified_at' => now(),            
                'telephone' => $this->faker->phoneNumber(),
                'mobile' => $mobile,
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password

                'payment_country_code' => 'tw',
                'payment_tin' => rand(10000000,99999999),
                'payment_company' => $this->faker->company(),
                'payment_department' => $this->faker->department(),
                
                'shipping_personal_name' => $name,
                'shipping_phone' => $mobile,
                'shipping_country_code' => 'tw',
                'shipping_company' => $this->faker->company(),
                'shipping_state_id' => $state_id,
                'shipping_city_id' => $city_id,
                'shipping_road' => $road,
                'shipping_address1' => $address1,

                'remember_token' => \Str::random(10),
            ]);
        }
        //User::factory()->count(100)->create(); 
        //User::factory()->count(3)->make();
    }
}
