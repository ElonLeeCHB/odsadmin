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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('code',10)->nullable();
            //$table->tinyInteger('scale')->default('0');
        });

        Schema::create('unit_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unit_id');
            $table->string('locale',10);
            $table->string('name',20);
            $table->string('description')->nullable();
        });

        Schema::create('unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_unit_id');
            $table->unsignedBigInteger('destination_unit_id');
            $table->decimal('source_quantity',17,8);
            $table->decimal('destination_quantity',17,8);
            $table->string('comment')->nullable();
            //$table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('unit_conversions');
        Schema::dropIfExists('unit_translations');
        Schema::dropIfExists('units');
    }
};
