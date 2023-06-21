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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code',20)->nullable();
            $table->string('name',100)->nullable();
            $table->string('comment')->nullable();
            $table->boolean('is_inventory')->default('1')->comment('0:非存貨倉, 1:存貨倉');
            $table->boolean('is_active')->default('1');
            $table->tinyInteger('sort_order')->default('999');
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
        Schema::dropIfExists('warehouses');
    }
};
