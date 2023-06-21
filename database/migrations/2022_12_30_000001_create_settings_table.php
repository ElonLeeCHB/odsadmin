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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('location_id')->nullable();
            $table->string('group');
            $table->string('setting_key')->nullable();
            $table->text('setting_value')->nullable();
            $table->string('name')->nullable();
            $table->string('comment')->nullable();
            $table->boolean('is_autoload')->default('0');
            $table->boolean('is_json')->default('0');
            $table->unique(['location_id','group','setting_key']);
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
        Schema::dropIfExists('settings');
    }
};
