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
        Schema::create('purchasing_receiving', function (Blueprint $table) {
            $table->id();
            $table->datetime('receiving_date')->index(); //收貨日
            $table->string('type_id',)->index(); //類別
            $table->timestamps();
        });

        Schema::create('purchasing_receiving_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('receiving_id')->index();
            $table->unsignedSmallInteger('sort_order')->default(1000);
            $table->unsignedInteger('type_id',)->nullable(); //類別

            $table->unsignedInteger('product_id',)->nullable(); //
            $table->string('product_name')->nullable(); //
            $table->string('product_specification')->nullable(); //

            $table->unsignedInteger('receiving_unit_id',)->nullable(); //
            $table->decimal('receiving_quantity', $precision = 13, $scale = 4)->nullable();
            
            $table->unsignedInteger('stock_unit_id',)->nullable(); //
            $table->decimal('stock_quantity', $precision = 13, $scale = 4)->nullable();

            $table->string('comment')->nullable(); //
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchasing_receiving_products');
        Schema::dropIfExists('purchasing_receiving');
    }




};
