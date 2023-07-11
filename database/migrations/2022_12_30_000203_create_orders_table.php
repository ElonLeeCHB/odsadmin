<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code',10)->unique();
            //$table->string('locale',10);
            $table->unsignedBigInteger('location_id'); // Seller, organizations.id
            $table->unsignedBigInteger('customer_id'); // Buyer, users.id
            $table->string('first_name',50)->nullable(); // users.first_name
            $table->string('last_name',50)->nullable(); // users.last_name
            $table->string('personal_name',50)->nullable(); // users.last_name            
            $table->string('email',100)->nullable();
            $table->string('mobile',20)->nullable();
            $table->string('telephone_prefix',3)->nullable();
            $table->string('telephone',20)->nullable();
            $table->date('order_date')->nullable();
            $table->string('payment_company',100)->nullable();
            $table->string('payment_department',100)->nullable();
            $table->string('payment_tin',20)->nullable(); //Tax Idenfication Number / uniform_invoice_numbers 
            // $table->string('payment_first_name',50)->nullable();
            // $table->string('payment_last_name',50)->nullable();
            //$table->string('payment_personal_name',50)->nullable();
            $table->string('payment_country',5)->nullable();
            $table->decimal('payment_total', $precision = 13, $scale = 4)->nullable();
            $table->decimal('payment_paid', $precision = 13, $scale = 4)->nullable();
            $table->decimal('payment_unpaid', $precision = 13, $scale = 4)->nullable();
            $table->string('payment_method',50)->nullable();
            $table->string('payment_comment')->nullable();
            $table->date('scheduled_payment_date')->nullable();
            $table->boolean('is_payment_tin')->default('0');
            $table->string('shipping_personal_name',50)->nullable();
            $table->string('shipping_country_code',5)->nullable();
            $table->string('shipping_company',100)->nullable();
            $table->string('shipping_phone',50)->nullable();
            $table->string('shipping_postal_code',10)->nullable();
            $table->unsignedBigInteger('shipping_state_id')->nullable();
            $table->unsignedBigInteger('shipping_city_id')->nullable();
            $table->string('shipping_road',100)->nullable();
            $table->string('shipping_address1',100)->nullable();
            $table->string('shipping_address2',100)->nullable();
            $table->string('shipping_road_abbr',50)->nullable();
            $table->datetime('shipping_date')->nullable()->comment('The date the product will leave the supplier’s warehouse');
            $table->string('shipping_time',5)->nullable();
            $table->string('shipping_method',50)->nullable();
            $table->datetime('delivery_date')->nullable()->comment('The date the package will make it to the customer’s doorstep');
            $table->string('delivery_time',5)->nullable();
            $table->string('delivery_time_range',30)->nullable();
            $table->string('delivery_time_comment',30)->nullable();
            $table->string('comment')->nullable(); //訂單備註、客戶備註
            $table->string('extra_comment')->nullable(); //餐點備註
            $table->unsignedInteger('status_id')->default('0'); //option id
            $table->boolean('is_closed')->default('0');
            $table->boolean('is_payed_off')->default('0');
            $table->string('old_code',10)->nullable(); //舊訂單編號(紙本)
            $table->timestamps();
        });
        
        Schema::create('order_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->tinyInteger('sort_order')->default('0');
            $table->string('model',50)->nullable();
            $table->string('name',50)->nullable();
            $table->string('main_category_code',50)->nullable();
            $table->smallInteger('quantity')->default('0');
            $table->decimal('price', $precision = 13, $scale = 4)->default('0');
            $table->decimal('total', $precision = 13, $scale = 4)->default('0');
            $table->decimal('options_total', $precision = 13, $scale = 4)->default('0');
            $table->decimal('final_total', $precision = 13, $scale = 4)->default('0');
            $table->decimal('tax', $precision = 13, $scale = 4)->default('0');
            $table->string('comment')->nullable();
            $table->timestamps();
            
            $table->unique(['order_id', 'product_id', 'sort_order']);
            //$table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
        
        Schema::create('order_product_options', function (Blueprint $table) {
            $table->id();
            //$table->unsignedInteger('order_product_id');
            //$table->foreignId('order_product_id')->constrained();
            $table->unsignedBigInteger('order_product_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_option_id');
            $table->unsignedBigInteger('product_option_value_id');
            $table->unsignedBigInteger('parent_product_option_value_id')->nullable();
            $table->string('name');
            $table->text('value');
            $table->string('type',30);
            $table->decimal('quantity', $precision = 13, $scale = 4)->default('0');
            $table->timestamps();
            
            $table->unique(['order_id','order_product_id','product_id','product_option_id', 'product_option_value_id', 'parent_product_option_value_id'],'order_product_options_unique_key');
            //$table->foreign('order_product_id')->references('id')->on('order_products')->onDelete('cascade');
        });
        
        Schema::create('order_totals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('code',20);
            $table->string('title',50);
            $table->decimal('value', $precision = 13, $scale = 4)->default('0');
            $table->tinyInteger('sort_order')->default('0');
            
            //$table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });

        // Schema::create('order_comments', function (Blueprint $table) {
        //     $table->id();
        // });

        // 訂單商品用料表
        Schema::create('order_product_ingredients', function (Blueprint $table) {
            $table->id();
            $table->date('required_date');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('order_product_id');
            $table->unsignedInteger('product_id');
            $table->string('product_name')->nullable();
            $table->unsignedInteger('sub_product_id');
            $table->string('sub_product_name')->nullable();
            $table->decimal('quantity',15,4);
            $table->timestamps();;
            $table->unique(['required_date','order_id','order_product_id','product_id','sub_product_id'], 'order_product_ingredients_unique_key');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('orders');
        Schema::dropIfExists('order_products');
        Schema::dropIfExists('order_product_options');
        Schema::dropIfExists('order_totals');
        //Schema::dropIfExists('order_comments');
        Schema::dropIfExists('order_product_ingredients');
        Schema::enableForeignKeyConstraints();
    }
};
