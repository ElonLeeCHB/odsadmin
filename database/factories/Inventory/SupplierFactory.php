<?php

namespace Database\Factories\Inventory;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $name = $this->faker->company();
        $short_name = mb_substr($name,0,3);

        return [
            'name' => $name,
            'short_name' => $short_name,
            'contact_name' => $this->faker->lastName() . $this->faker->firstName(),
            'contact_title' => $this->faker->jobTitle(),
            'telephone' => $this->faker->phoneNumber(),
            'mobile' => $this->faker->phoneNumber(),
            'is_active' => 1,
        ];
    }
}
