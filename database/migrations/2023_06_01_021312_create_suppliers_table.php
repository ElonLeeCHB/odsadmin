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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code',20)->default('');
            $table->string('name',50)->default('');
            $table->string('short_name',20)->default('');
            $table->string('contact_name',50)->default('');
            $table->string('contact_title',50)->default('');
            $table->string('telephone',20)->default('');
            $table->string('mobile',20)->default('');
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
        Schema::dropIfExists('suppliers');
    }
};
