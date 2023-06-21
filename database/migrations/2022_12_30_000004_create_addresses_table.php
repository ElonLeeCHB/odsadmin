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
        Schema::create('divisions', function (Blueprint $table) {
            $table->id();
            $table->string('code',10)->nullable()->comment('basically iso 3166');
            //$table->unsignedInteger('country_id');
            $table->string('country_code',2);
            $table->tinyInteger('level');
            $table->unsignedInteger('parent_id');
            $table->string('name',100)->nullable();
            $table->string('english_name',100)->nullable();
            $table->string('postal_code',10)->nullable();
            $table->boolean('is_active')->default('1');
            $table->index(['country_code','level','parent_id']);
            $table->unsignedSmallInteger('sort_order')->nullable();
        });

        Schema::create('roads', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('city_id');
            $table->string('name',100);
            $table->unsignedSmallInteger('sort_order')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('roads');
        Schema::dropIfExists('divisions');
    }
};
