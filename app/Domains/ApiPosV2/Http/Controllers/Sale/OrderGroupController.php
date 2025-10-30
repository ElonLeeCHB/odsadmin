<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use App\Helpers\Classes\DateHelper;
use App\Helpers\Classes\OrmHelper;
use App\Enums\Sales\InvoiceStatus;

/*
public function index()    // GET 所有資料
public function store()    // POST 新增
public function show()     // GET 單筆資料
public function update()   // PUT/PATCH 更新
public function destroy()  // DELETE 刪除
*/

use App\Models\Sale\OrderGroup;
use App\Models\Sale\Order;
use App\Models\Sale\Invoice;
use Illuminate\Support\Facades\DB;

class OrderGroupController extends ApiPosController
{
    /**
     * GET /order-groups
     * 列出所有群組
     */
    public function index()
    {
        try {
            $groups = OrderGroup::withCount('orders')->latest()->paginate(10);

            return $this->sendJsonResponse($groups);
        } catch (\Throwable $th) {
            return $this->sendJsonResponse(data: ['error' => $th->getMessage()]);
        }


    }

    /**
     * POST /order-groups
     * 建立新的訂單群組
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
            // 'order_id' => 'required|exists:orders,id',  // 必須帶入一筆存在的 order
            'order_id' => 'required|integer', // 只驗格式，沒查 DB
        ]);

        $order = Order::find($validated['order_id']);

        if (!$order) {
            return response()->json(['success' => false, 'message' => '訂單不存在',], 404);
        }

        if ($order->order_group_id) {
            return response()->json(['success' => false,'message' => '該訂單已屬於其他群組，請先退出群組。',], 409);
        }

        // 建立群組
        $group = OrderGroup::create([
            'notes'      => $validated['notes'] ?? null,
            'creator_id' => auth()->id(),
        ]);
        
        // 更新該筆訂單的 order_group_id
        Order::where('id', $validated['order_id'])->update(['order_group_id' => $group->id]);

        return response()->json(['success' => true, 'data' => $group], 201);
    }

    /**
     * GET /order-groups/{id}
     * 查詢單一群組
     */
    public function show($id)
    {
        $group = OrderGroup::with(['orders' => function ($query) {
            $query->select('id','order_group_id','code','customer_id','personal_name');
        }])->findOrFail($id);

        // 關閉所有關聯訂單的 appended attributes
        $group->orders->each->setAppends([]);

        return response()->json($group);
    }

    /**
     * PUT /order-groups/{id}
     * 更新群組資料（名稱、備註）
     */
    public function update(Request $request, $id)
    {
        $group = OrderGroup::findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $group->update([
            'name' => $validated['name'] ?? $group->name,
            'notes' => $validated['notes'] ?? $group->notes,
            'modifier_id' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'data' => $group]);
    }

    /**
     * DELETE /order-groups/{id}
     * 刪除群組及關聯
     */
    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $group = OrderGroup::findOrFail($id);
            $group->orders()->update(['order_group_id' => null]); // 可選，保留 order
            $group->delete();
        });

        return response()->json(['success' => true]);
    }

    /**
     * POST /order-groups/{id}/attach-order
     * 附加訂單至群組
     */
    public function attachOrder(Request $request, $id)
    {
        $group = OrderGroup::findOrFail($id);

        $validated = $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:orders,id',
        ]);

        Order::whereIn('id', $validated['order_ids'])
            ->update(['order_group_id' => $group->id]);

        return response()->json(['success' => true]);
    }

    /**
     * POST /order-groups/{id}/detach-order
     * 從群組中移除訂單
     */
    public function detachOrder(Request $request, $id)
    {
        $group = OrderGroup::findOrFail($id);
        $validated = $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:orders,id',
        ]);

        Order::whereIn('id', $validated['order_ids'])
            ->where('order_group_id', $group->id)
            ->update(['order_group_id' => null]);

        return response()->json(['success' => true]);
    }

    /**
     * POST /order-groups/{id}/attach-invoice
     */
    public function attachInvoice(Request $request, $id)
    {
        $validated = $request->validate([
            'invoice_ids' => 'required|array',
            'invoice_ids.*' => 'integer|exists:invoices,id',
        ]);

        // 將發票加入群組
        $count = Invoice::whereIn('id', $validated['invoice_ids'])
            ->update(['order_group_id' => $id]);

        return response()->json([
            'success' => true,
            'message' => "{$count} 張發票已成功加入群組。",
        ]);
    }

    /**
     * POST /order-groups/{id}/detach-invoice
     */
    public function detachInvoice(Request $request, $id)
    {
        $validated = $request->validate([
            'invoice_ids' => 'required|array',
            'invoice_ids.*' => 'integer|exists:invoices,id',
        ]);

        $invoices = Invoice::whereIn('id', $validated['invoice_ids'])
            ->where('order_group_id', $id)
            ->get();

        $detached = 0;
        $failed = [];

        foreach ($invoices as $invoice) {
            if ($invoice->status === InvoiceStatus::Canceled) {
                $invoice->order_group_id = null;
                $invoice->save();
                $detached++;
            } else {
                $failed[] = [
                    'id' => $invoice->id,
                    'status' => $invoice->status->value,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'detached_count' => $detached,
            'failed_to_detach' => $failed,
            'message' => $detached . ' 張發票已移出群組。' .
                (count($failed) ? '未作廢的發票無法移除。' : ''),
        ]);
    }
}
