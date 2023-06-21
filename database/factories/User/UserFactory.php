<?php

namespace Database\Factories\User;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Localization\Road;
use App\Faker\zh_Hant\Department;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $this->faker->addProvider(new Department($this->faker));

        $state_id = rand(1,22);
        $city_id = rand(23,393);
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

        return [
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
            'shipping_road_id' => $road_id,
            'shipping_road' => $road,
            'shipping_address1' => $address1,

            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return static
     */
    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }
}
