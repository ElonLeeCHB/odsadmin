<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
//faker
use Faker\Generator;
use Illuminate\Container\Container;
//
use App\Models\Catalog\Product;
use DB;

class ProductSeeder extends Seeder
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
        // products
        DB::table('products')->truncate();

        for ($i=1; $i < 100; $i++) { 
            $randDigit = rand(5,15);
            $slug = $this->faker->text();
            $slug = trim(substr($slug, 0, $randDigit));
            $slug = str_replace(' ', '-', $slug);
            $model = $this->faker->text();
            $model = trim(substr($model, 0, $randDigit));
            $model = str_replace(' ', '-', $model);

            Product::create([
                'slug' => $slug,
                'model' => $model,
                'price' => rand(50,300),
                'quantity' => rand(1,500),
                'is_active' => 0,
                ]);
        }

        // product_translations
        DB::table('product_translations')->truncate();
        
    }
}
