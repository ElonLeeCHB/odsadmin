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
        Schema::create('financial_institutions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('parent_id')->default('0');
            $table->string('code',20)->nullable();
            $table->string('name',100)->nullable();
            $table->string('short_name',100)->nullable();
            $table->boolean('is_active')->default('1');
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
        Schema::dropIfExists('financial_institutions');
    }
};
