<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Sale\Invoice;
use App\Models\Sale\InvoiceItem;
use App\Repositories\Eloquent\Sale\InvoiceRepository;
use Illuminate\Support\Str;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('invoice_items')->truncate();
        DB::table('invoices')->truncate();
        
        $faker = \Faker\Factory::create();

        foreach (range(1, 15) as $i) {
            $hasTaxId = $faker->boolean(70); // 70% 有統編
            $taxIdNumber = $hasTaxId ? str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT) : null;

            $itemCount = rand(1, 5);

            // 模擬訂單明細（含稅小計）
            $invoiceItems = [];
            $totalAmount = 0;
            
            for ($j = 0; $j < $itemCount; $j++) {
                $quantity = $faker->numberBetween(3, 300);
                $unitPrice = round($faker->randomFloat(3, 45, 300), 3);
                $subtotal = round($unitPrice * $quantity, 3); // ✅ 保留三位

                $invoiceItems[] = [
                    'quantity' => $quantity,
                    'final_total' => $subtotal,
                ];

                $totalAmount += $subtotal;
            }

            $invoice = [
                'invoice_number' => 'AA' . str_pad((string)($i), 8, '0', STR_PAD_LEFT),
                'invoice_date' => now()->subDays(rand(0, 30)),
                'buyer_name' => $faker->company,
                'seller_name' => $faker->company,
                'tax_id_number' => $taxIdNumber,
                'customer_id' => null,
                'tax_type' => 'taxable',
                'status' => 'paid',
                'creator_id' => 1,
                'modifier_id' => 1,
                'total_amount' => $totalAmount, // 設定總金額
            ];

            // 計算稅額與單身
            $result = InvoiceRepository::calculateInvoiceItemPrices($invoice, $invoiceItems);

            $invoiceModel = Invoice::create($invoice);

            $invoiceModel->tax_amount = $result['tax_amount'];
            $invoiceModel->total_amount = $result['total_amount'];
            $invoiceModel->save();

            // 建立 invoice_items
            foreach ($result['items'] as $index => $itemData) {
                InvoiceItem::create([
                    'invoice_id' => $invoiceModel->id,
                    'sort_order' => $index + 1,
                    'name' => $faker->word,
                    'is_tax_included' => !$hasTaxId,
                    'quantity' => $itemData['quantity'],
                    'price' => $itemData['price'],
                    'subtotal' => $itemData['subtotal'],
                ]);
            }
        }
    }
}
