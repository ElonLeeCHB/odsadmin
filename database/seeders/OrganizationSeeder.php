<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
//faker
use Faker\Generator;
use Illuminate\Container\Container;
//
use App\Models\Member\Organization;

class OrganizationSeeder extends Seeder
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

        for ($i=1; $i < 99; $i++) {
            $code = 'C2211'.sprintf("%04d", $i);
            $name = $this->faker->company();
            Organization::create([
                'code' => $code,
                'name' => $name,
                'short_name' => $name,
                'telephone' => $this->faker->phoneNumber(),
                'country_code' => 'tw',
                'is_juridical_entity' => '1',
                'is_active' => '1',
                ]);
        }
    }
}
