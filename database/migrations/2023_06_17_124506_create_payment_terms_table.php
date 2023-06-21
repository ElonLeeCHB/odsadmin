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
        Schema::create('payment_terms', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('type')->default('1')->comment('1.採購  2.銷售');
            $table->string('name');
            $table->string('description')->nullable();
            $table->tinyInteger('due_data_basis')->comment('1.來源單據日 2.出貨日(到貨日) 3.次月初');
            $table->tinyInteger('due_date_plus_months')->comment('應收款日為起算日起加幾月');
            $table->tinyInteger('due_date_plus_days')->comment('應收款日為起算日起加幾天');
            $table->tinyInteger('sort_order')->default('100');
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
        Schema::dropIfExists('payment_terms');
    }
};
