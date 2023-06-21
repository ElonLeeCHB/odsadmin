<?php

namespace Database\Seeders\Xlinfoods;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            UserSeeder::class,
            OrganizationSeeder::class,
            TermSeeder::class,
            OptionSeeder::class,
            ProductSeeder::class,
            OrderSeeder::class,
            
        ]);
    }
}
 