<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Sale;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use App\Helpers\Classes\DateHelper;
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

            return $this->sendJsonResponse($invoices);
        } catch (\Throwable $th) {
            return $this->sendJsonResponse(data: ['error' => $th->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $invoice = Invoice::findOrFail($id);

            return $this->sendJsonResponse($invoice);
        } catch (\Throwable $th) {
            return $this->sendJsonResponse(data: ['error' => $th->getMessage()]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $invoice = new Invoice();

        // 不讓外部帶入 invoice_number
        unset($request['invoice_number']);

        // 自動產生 invoice_number
        $invoice->invoice_number = $this->generateInvoiceNumber();

        return $this->save($invoice, $request);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $invoice = Invoice::findOrFail($id);
        return $this->save($invoice, $request);
    }

    private function save(Invoice $invoice, Request $request)
    {
        $validated = $request->validate([
            'invoice_date' => 'required|date',
            'customer_name' => 'nullable|string|max:20',
            'seller_name' => 'nullable|string|max:20',
            'tax_id_number' => 'nullable|string|max:12',
            'total' => 'required|integer|min:0',
            'status' => 'required|in:unpaid,paid,canceled',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer|exists:invoice_items,id',
            'items.*.name' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.amount' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($invoice, $validated) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = $this->generateInvoiceNumber();
            }

            $invoice->invoice_date = $validated['invoice_date'];
            $invoice->customer_name = $validated['customer_name'];
            $invoice->seller_name = $validated['seller_name'];
            $invoice->tax_id_number = $validated['tax_id_number'];
            $invoice->total = $validated['total'];
            $invoice->status = $validated['status'];
            $invoice->save();

            // 取得資料庫中該 invoice 所有 items id
            $existingIds = $invoice->invoiceItems()->pluck('id')->toArray();

            // 輸入 items 中有的 id (排除空值)
            $inputIds = collect($validated['items'])->pluck('id')->filter()->toArray();

            // 找出要刪除的 id（資料庫有但輸入沒有）
            $idsToDelete = array_diff($existingIds, $inputIds);

            if (!empty($idsToDelete)) {
                $invoice->invoiceItems()->whereIn('id', $idsToDelete)->delete();
            }

            foreach ($validated['items'] as $item) {
                if (!empty($item['id'])) {
                    // 更新既有明細
                    $invoiceItem = $invoice->invoiceItems()->find($item['id']);
                    if ($invoiceItem) {
                        $invoiceItem->update([
                            'description' => $item['name'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unit_price'],
                            'amount' => $item['amount'],
                        ]);
                    }
                } else {
                    // 新增明細
                    $invoice->invoiceItems()->create([
                        'description' => $item['name'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'amount' => $item['amount'],
                    ]);
                }
            }

            return response()->json($invoice->load('items'));
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Invoice::destroy($id);
        return response()->json(['message' => 'Deleted successfully']);
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
