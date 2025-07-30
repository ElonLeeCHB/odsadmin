<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Enums\InvoiceStatus;

class InvoicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = InvoiceStatus::values();

        $invoices = [];

        for ($i = 1; $i <= 1000; $i++) {
            $invoices[] = [
                'invoice_number' => 'INV' . now()->format('Ymd') . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'invoice_date' => Carbon::now()->subDays(rand(0, 365))->format('Y-m-d'),
                'amount' => rand(100, 50000),
                'status' => $statuses[array_rand($statuses)],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('invoices')->insert($invoices);
    }
}
