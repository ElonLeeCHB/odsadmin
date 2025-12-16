<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_channel_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->tinyInteger('channel_code')->unsigned()->comment('通路代碼，關聯 terms.code (WHERE taxonomy_code=sales_channel)');
            $table->decimal('price', 13, 4)->comment('通路售價');
            $table->date('start_date')->nullable()->comment('生效日期，NULL=立即生效');
            $table->date('end_date')->nullable()->comment('結束日期，NULL=永久有效');
            $table->tinyInteger('is_active')->default(1)->comment('啟用狀態');
            $table->timestamps();

            $table->index(['product_id', 'channel_code'], 'idx_product_channel');
            $table->index(['channel_code', 'is_active'], 'idx_channel_active');
            $table->index(['start_date', 'end_date'], 'idx_date_range');

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });

        // 資料表註解
        DB::statement("ALTER TABLE product_channel_prices COMMENT '商品通路價格表。channel_code 關聯 terms.code (條件: taxonomy_code=sales_channel)'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_channel_prices');
    }
};
