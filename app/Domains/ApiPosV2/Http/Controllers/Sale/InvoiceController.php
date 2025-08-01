<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Sale;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use App\Helpers\Classes\DateHelper;
use App\Helpers\Classes\LogHelper;
use App\Helpers\Classes\OrmHelper;
use App\Models\Sale\Invoice;

/*
public function index()    // GET 所有資料
public function store()    // POST 新增
public function show()     // GET 單筆資料
public function update()   // PUT/PATCH 更新
public function destroy()  // DELETE 刪除
*/

class InvoiceController extends ApiPosController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $filter_data = $this->all_data;

            $query = Invoice::query();
            OrmHelper::prepare($query, $filter_data);

            $invoices = OrmHelper::getResult($query, $filter_data);

            return response()->json(['success' => true, 'data' => $invoices], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $th) {
            return $this->sendJsonErrorResponse(response: ['sys_error' => $th->getMessage()], status_code: 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $invoice = Invoice::findOrFail($id);

            return response()->json(['success' => true, 'data' => $invoice], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $th) {
            return $this->sendJsonErrorResponse(response: ['sys_error' => $th->getMessage()], status_code: 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $invoice = new Invoice();

        $invoice->invoice_number = $this->generateInvoiceNumber();

        return $this->save($invoice, $request);
    }

    /**
     * Update the specified resource in storage.
     */
    // public function update(Request $request, string $id)
    public function update(Request $request, Invoice $invoice)
    {
        return $this->save($invoice, $request);
    }

    private function save(Invoice $invoice, Request $request)
    {
        $validated = $request->validate([
            'invoice_date' => 'required|date',
            'buyer_name' => 'nullable|string|max:20',
            'seller_name' => 'nullable|string|max:20',
            'tax_id_number' => 'nullable|string|max:12',
            'total_amount' => 'required|integer|min:0',
            'status' => 'required|in:unpaid,paid,canceled',
            'invoice_items' => 'required|array|min:1',
            'invoice_items.*.id' => 'nullable|integer',
            'invoice_items.*.name' => 'required|string|max:255',
            'invoice_items.*.quantity' => 'required|integer|min:1',
            'invoice_items.*.price' => 'required|numeric|min:0',
            'invoice_items.*.subtotal' => 'required|numeric|min:0',
        ], [
            'invoice_date.required' => '請輸入發票日期',
            'invoice_date.date' => '發票日期格式不正確',
            'buyer_name.string' => '買受人名稱必須是文字',
            'buyer_name.max' => '買受人名稱最多 20 個字',
            'seller_name.string' => '賣方名稱必須是文字',
            'seller_name.max' => '賣方名稱最多 20 個字',
            'tax_id_number.string' => '統一編號必須是文字',
            'tax_id_number.max' => '統一編號最多 12 個字',
            'total_amount.required' => '請輸入總金額',
            'total_amount.integer' => '總金額必須為整數',
            'total_amount.min' => '總金額不能為負數',
            'status.required' => '請選擇狀態',
            'status.in' => '狀態只能是 unpaid、paid 或 canceled',
            'invoice_items.required' => '請至少填寫一筆品項',
            'invoice_items.array' => '品項格式錯誤',
            'invoice_items.min' => '請至少填寫一筆品項',
            'invoice_items.*.id.integer' => '品項 ID 必須為整數',
            'invoice_items.*.name.required' => '請輸入品項名稱',
            'invoice_items.*.name.string' => '品項名稱必須是文字',
            'invoice_items.*.name.max' => '品項名稱最多 255 個字',
            'invoice_items.*.quantity.required' => '請輸入數量',
            'invoice_items.*.quantity.integer' => '數量必須為整數',
            'invoice_items.*.quantity.min' => '數量至少為 1',
            'invoice_items.*.price.required' => '請輸入單價',
            'invoice_items.*.price.numeric' => '單價必須為數字',
            'invoice_items.*.price.min' => '單價不能為負數',
            'invoice_items.*.subtotal.required' => '請輸入小計',
            'invoice_items.*.subtotal.numeric' => '小計必須為數字',
            'invoice_items.*.subtotal.min' => '小計不能為負數',
        ]);

        try {
            DB::beginTransaction();

            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = $this->generateInvoiceNumber();
            }

            $invoice->invoice_date = $validated['invoice_date'];
            $invoice->buyer_name = $validated['buyer_name'];
            $invoice->seller_name = $validated['seller_name'];
            $invoice->tax_id_number = $validated['tax_id_number'];
            $invoice->total_amount = $validated['total_amount'];
            $invoice->status = $validated['status'];
            $invoice->save();

            // 取得資料庫中該 invoice 所有 items id
            $existingIds = $invoice->invoiceItems()->pluck('id')->toArray();

            // 輸入 items 中有的 id (排除空值)
            $inputIds = collect($validated['invoice_items'])->pluck('id')->filter()->toArray();

            // 找出要刪除的 id（資料庫有但輸入沒有）
            $idsToDelete = array_diff($existingIds, $inputIds);

            if (!empty($idsToDelete)) {
                $invoice->invoiceItems()->whereIn('id', $idsToDelete)->delete();
            }

            foreach ($validated['invoice_items'] as $item) {
                if (!empty($item['id'])) {
                    // 更新既有明細
                    $invoiceItem = $invoice->invoiceItems()->find($item['id']);
                    if ($invoiceItem) {
                        $invoiceItem->update([
                            'description' => $item['name'],
                            'quantity' => $item['quantity'],
                            'price' => $item['price'],
                            'subtotal' => $item['subtotal'],
                        ]);
                    }
                } else {
                    // 新增明細
                    $invoice->invoiceItems()->create([
                        'description' => $item['name'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'subtotal' => $item['subtotal'],
                    ]);
                }
            }

            DB::commit();

            $json = [
                'success' => true,
                'message' => '發票儲存成功',
                'data' => $invoice->load('invoiceItems'),
            ];

            return response()->json($json, 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->sendJsonErrorResponse(response: ['sys_error' => $th->getMessage()], status_code: 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            Invoice::destroy($id);

            return response()->json(['success' => true, 'message' => '刪除成功']);
        } catch (\Throwable $th) {
            return $this->sendJsonErrorResponse(response: ['sys_error' => $th->getMessage()], status_code: 500);
        }
    }

    protected function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = now()->format('y'); // 25
        $month = now()->format('m'); // 07

        $base = $prefix . $year . $month;

        // 找當月最新編號
        $last = \App\Models\Sale\Invoice::where('invoice_number', 'like', "{$base}%")
            ->orderByDesc('invoice_number')
            ->first();

        if ($last) {
            $lastSerial = (int)substr($last->invoice_number, -4);
            $nextSerial = $lastSerial + 1;
        } else {
            $nextSerial = 1;
        }

        return $base . str_pad($nextSerial, 4, '0', STR_PAD_LEFT);
    }
}
