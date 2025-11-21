<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->nullable()->unique()->comment('門市代碼');
            $table->string('name')->comment('門市名稱');
            $table->unsignedBigInteger('state_id')->nullable()->comment('縣市 division_id');
            $table->unsignedBigInteger('city_id')->nullable()->comment('鄉鎮市區 division_id');
            $table->text('address')->nullable()->comment('地址');
            $table->string('phone', 50)->nullable()->comment('電話');
            $table->unsignedBigInteger('manager_id')->nullable()->comment('店長 user_id');
            $table->boolean('is_active')->default(true)->comment('是否啟用');
            $table->timestamps();

            $table->index('code');
            $table->index('state_id');
            $table->index('city_id');
            $table->index('manager_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
