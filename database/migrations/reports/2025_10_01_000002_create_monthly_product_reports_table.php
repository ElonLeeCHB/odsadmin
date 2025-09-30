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
        Schema::connection('sysdata')->create('monthly_product_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->comment('年份');
            $table->unsignedTinyInteger('month')->comment('月份');
            $table->string('product_code', 100)->comment('商品代號');
            $table->string('product_name', 255)->comment('商品名稱');
            $table->decimal('quantity', 15, 3)->default(0)->comment('銷售數量');
            $table->decimal('total_amount', 15, 2)->default(0)->comment('銷售金額');
            $table->timestamps();

            $table->unique(['year', 'month', 'product_code'], 'uk_year_month_product');
            $table->index(['year', 'month', 'total_amount'], 'idx_year_month_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sysdata')->dropIfExists('monthly_product_reports');
    }
};
