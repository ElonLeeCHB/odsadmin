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
        Schema::create('material_requisitions', function (Blueprint $table) {
            $table->id();
            $table->datetime('required_date'); //需求日
            $table->unsignedBigInteger('material_product_id'); //
            $table->smallInteger('quantity')->default('0'); //
            $table->timestamps();
            $table->index(['required_date', 'material_product_id']);
        });

        Schema::create('material_requisition_details', function (Blueprint $table) {
            $table->id();
            //$table->unsignedBigInteger('requisition_id'); // material_requisitions -> id
            $table->datetime('required_date')->index(); //需求日
            $table->unsignedBigInteger('material_product_id')->index(); //
            $table->unsignedBigInteger('product_id')->default('0'); // ex. order's product
            $table->smallInteger('quantity')->default('0'); //
            $table->enum('source', ['none','orders', 'demand_forcastings'])->default('none'); // 訂單 orders, 需求預測 demand_forcastings
            $table->unsignedBigInteger('source_id')->default('0'); //
            $table->unsignedBigInteger('source_body_id')->default('0'); //
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('material_requisition_details');
        Schema::dropIfExists('material_requisitions');
    }
};
