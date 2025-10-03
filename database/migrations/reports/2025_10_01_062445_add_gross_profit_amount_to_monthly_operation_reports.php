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
        Schema::connection('sysdata')->table('monthly_operation_reports', function (Blueprint $table) {
            $table->decimal('gross_profit_amount', 15, 2)->default(0)->after('purchase_total_amount')->comment('毛利金額');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sysdata')->table('monthly_operation_reports', function (Blueprint $table) {
            $table->dropColumn('gross_profit_amount');
        });
    }
};
