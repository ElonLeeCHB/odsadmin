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
        Schema::dropIfExists('order_ingredients');
    }
};
