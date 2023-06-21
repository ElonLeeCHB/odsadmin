<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Generator;
use Illuminate\Container\Container;
use App\Models\User\User;
use App\Models\Sale\Order;
use App\Models\Sale\OrderProduct;
use App\Models\Catalog\Product;
use App\Models\Localization\Division;

class OrderSeeder extends Seeder
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
        for ($i=1; $i < 102; $i++) {
            $year = 22;
            $month = sprintf("%02d",11);
            $no = sprintf("%04d", $i);
            $code = $year . $month . $no;

            $customer_id = rand(2,20);
            $customer = User::find($customer_id);
            // $code = 'S'.sprintf("%06d", $i);

            $order_date = $this->faker->dateTimeBetween('+5 week', '+5 month');
            $order_date = date_format($order_date,"Y-m-d");
            
            $datetime = $this->faker->dateTimeBetween('+5 week', '+5 month');

            $delivery_time_scheduled = date_format($datetime,"Y-m-d H:i:s");

            $rand = rand(2,3);
            $shipping_date = date_add($datetime,date_interval_create_from_date_string("-".$rand." day"));
            $shipping_date = date_format($shipping_date,"Y-m-d H:i:s");

            $rand = rand(4,6);
            $delivery_time = date_add($datetime,date_interval_create_from_date_string($rand." day"));
            $delivery_time = date_format($delivery_time,"Y-m-d H:i:s");
        
            Order::create([
                'id' => $i,
                'code' => $code,
                'store_id' => '1',
                'customer_id' => $customer->id,
                'personal_name' => $customer->personal_name,
                'email' => $this->faker->email(),
                'telephone' => $this->faker->phoneNumber(),
                'mobile' => $this->faker->phoneNumber(),
                'order_date' => $order_date,
                'payment_country' => 'tw',                
                'payment_tin' => $customer->payment_tin,
                'payment_company' => $this->faker->company(),
                'payment_department' => $this->faker->department(),
                'shipping_company' => $this->faker->company(),
                'shipping_personal_name' => $customer->personal_name,
                'shipping_phone' => $this->faker->phoneNumber(),
                'shipping_country_code' => 'tw',
                'shipping_state_id' => $customer->shipping_state_id,
                'shipping_city_id' => $customer->shipping_city_id,
                'shipping_road' => $customer->shipping_road,
                'shipping_address1' => $customer->shipping_address1,
                'shipping_date' => $shipping_date,
                'delivery_time_scheduled' => $delivery_time_scheduled,
                'delivery_time' => $delivery_time,
                'is_closed' => '0',
                ]);

                if($i > 100){
                    break;
                }

                //OrderProduct
                $productCount = rand(1,9);
                for ($j=1; $j < $productCount; $j++) {
                    $product_id = rand(1,99);
                    $product = Product::find($product_id);
                    $quantity = rand(1,$product->quantity);
                    OrderProduct::create([
                        'order_id' => $i,
                        'product_id' => rand(1,99),
                        'model' => $product->model,
                        'name' => $product->model,
                        'quantity' => $quantity,
                        'price' => $product->price,
                        'subtotal' => $quantity*$product->price,
                        'sort_order' => $j,
                    ]);
                }

        }
    }
}
