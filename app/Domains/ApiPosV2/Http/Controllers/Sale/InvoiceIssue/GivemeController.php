<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Sale\InvoiceIssue;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use App\Models\Sale\Invoice;

/**
 * Giveme 電子發票正式環境控制器
 *
 * 用途：正式環境的發票開立流程（從資料庫讀取）
 * 環境：僅限 production 環境使用
 * 特點：使用正式帳號，僅在正式環境執行
 *
 * ⚠️ 重要：此控制器僅能在 APP_ENV=production 時執行
 * 非 production 環境請使用 GivemeTestController
 */

/*
  ✅ Giveme 電子發票正式環境路徑

  基礎路徑

  http://your-domain.com/api/posv2/sales/invoice-issue/giveme

  可用端點

  | 方法   | 路徑                          | 說明                                     |
  |------|------------------------------|----------------------------------------|
  | POST | /issue                       | 開立發票（需提供 invoice_id, order_id, order_code） |
  | POST | /cancel                      | 作廢發票（需提供 invoice_id, reason）             |
  | POST | /query                       | 查詢發票（需提供 invoice_id）                    |
  | GET  | /print-url/{invoice_number}  | 取得發票列印 URL（需提供 invoice_number）         |

  完整 URL 範例

  # 1. 開立發票
  POST http://your-domain.com/api/posv2/sales/invoice-issue/giveme/issue
  Body: {
    "invoice_id": 123,
    "order_id": 456,
    "order_code": "ORD20250101001"
  }

  # 2. 作廢發票
  POST http://your-domain.com/api/posv2/sales/invoice-issue/giveme/cancel
  Body: {
    "invoice_id": 123,
    "reason": "客戶要求作廢"
  }

  # 3. 查詢發票
  POST http://your-domain.com/api/posv2/sales/invoice-issue/giveme/query
  Body: {
    "invoice_id": 123
  }

  # 4. 取得發票列印 URL
  GET http://your-domain.com/api/posv2/sales/invoice-issue/giveme/print-url/JN80000776
*/

class GivemeController extends ApiPosController
{
    /**
     * API 基礎 URL
     */
    protected string $apiUrl;

    /**
     * 正式環境參數
     */
    protected string $taxId;
    protected string $account;
    protected string $password;

    /**
     * 建構子 - 檢查環境並初始化設定
     */
    public function __construct()
    {
        parent::__construct();

        // 環境檢查：僅允許 production 環境使用
        if (config('app.env') !== 'production') {
            abort(403, '此 API 僅限正式環境使用，請使用 GivemeTestController 進行測試');
        }

        $this->apiUrl = config('invoice.giveme.api_url');
        $this->taxId = config('invoice.giveme.tax_id');
        $this->account = config('invoice.giveme.account');
        $this->password = config('invoice.giveme.password');
    }

    /**
     * 開立發票（從資料庫讀取）
     *
     * POST /api/posv2/sales/invoice-issue/giveme/issue
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function issue(Request $request)
    {
        try {
            // 驗證輸入
            $request->validate([
                'invoice_id' => 'required|integer',
                'order_id' => 'nullable|integer',
                'order_code' => 'nullable|string',
            ]);

            $invoiceId = $request->input('invoice_id');
            $orderId = $request->input('order_id');
            $orderCode = $request->input('order_code');

            // 從資料庫讀取發票
            $invoice = Invoice::with('invoiceItems')->find($invoiceId);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => '找不到發票',
                    'invoice_id' => $invoiceId,
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // 檢查發票是否已開立（允許 PENDING 或 PENDING_* 開頭的臨時號碼）
            if (!empty($invoice->invoice_number)
                && $invoice->invoice_number !== 'PENDING'
                && !str_starts_with($invoice->invoice_number, 'PENDING_')) {
                return response()->json([
                    'success' => false,
                    'message' => '發票已開立',
                    'invoice_number' => $invoice->invoice_number,
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            // 判斷是 B2C 還是 B2B
            $isB2B = !empty($invoice->tax_id_number);

            // 組裝機迷坊 API 請求資料
            if ($isB2B) {
                $result = $this->issueB2B($invoice, $orderId, $orderCode);
            } else {
                $result = $this->issueB2C($invoice, $orderId, $orderCode);
            }

            return $result;

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '參數驗證失敗',
                'errors' => $e->errors(),
            ], 422, [], JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $th) {
            Log::error('Giveme Production Issue Error', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return $this->sendJsonErrorResponse(
                data: ['error' => $th->getMessage()],
                status_code: 500
            );
        }
    }

    /**
     * 開立 B2C 發票
     *
     * @param Invoice $invoice
     * @param int|null $orderId
     * @param string|null $orderCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function issueB2C(Invoice $invoice, $orderId = null, $orderCode = null)
    {
        DB::beginTransaction();

        try {
            $timeStamp = round(microtime(true) * 1000);
            $sign = strtoupper(md5($timeStamp . $this->account . $this->password));

            // 組裝商品明細
            $items = [];
            foreach ($invoice->invoiceItems as $item) {
                $items[] = [
                    'name' => $item->name,
                    'money' => (int)$item->price,
                    'number' => (int)$item->quantity,
                    'remark' => $item->remark ?? '',
                ];
            }

            // 組裝請求資料
            $requestData = [
                'timeStamp' => (string)$timeStamp,
                'uncode' => $this->taxId,
                'idno' => $this->account,
                'sign' => $sign,
                'customerName' => $invoice->buyer_name ?? '',
                'datetime' => \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d'),
                'email' => $invoice->email ?? '',
                'state' => $invoice->carrier_type === 'donation' ? '1' : '0',
                'taxType' => $this->mapTaxType($invoice->tax_type),
                'totalFee' => (string)$invoice->total_amount,
                'content' => $invoice->content ?? '商品銷售',
                'items' => $items,
            ];

            // 處理載具
            if ($invoice->carrier_type === 'donation') {
                $requestData['donationCode'] = $invoice->donation_code;
            } elseif ($invoice->carrier_type === 'phone_barcode') {
                $requestData['phone'] = $invoice->carrier_number;
            } elseif ($invoice->carrier_type !== 'none' && !empty($invoice->carrier_number)) {
                $requestData['orderCode'] = $invoice->carrier_number;
            }

            Log::info('Giveme Production B2C Request', [
                'invoice_id' => $invoice->id,
                'request' => $requestData,
            ]);

            // 發送請求
            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . '?action=addB2C', $requestData);

            $responseData = $response->json();

            Log::info('Giveme Production B2C Response', [
                'invoice_id' => $invoice->id,
                'status' => $response->status(),
                'response' => $responseData,
            ]);

            // 更新資料庫
            if (isset($responseData['success']) && $responseData['success'] === 'true') {
                $invoice->invoice_number = $responseData['code'];
                $invoice->random_code = $responseData['randomCode'] ?? null;
                $invoice->api_request_data = json_encode($requestData);
                $invoice->api_response_data = json_encode($responseData);
                $invoice->giveme_status = '0';
                $invoice->save();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'B2C 發票開立成功',
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'response' => $responseData,
                ], 200, [], JSON_UNESCAPED_UNICODE);
            } else {
                // API 回應失敗
                $invoice->api_request_data = json_encode($requestData);
                $invoice->api_response_data = json_encode($responseData);
                $invoice->api_error = $responseData['msg'] ?? 'API 回應失敗';
                $invoice->save();

                DB::commit();

                return response()->json([
                    'success' => false,
                    'message' => 'B2C 發票開立失敗',
                    'invoice_id' => $invoice->id,
                    'error' => $responseData['msg'] ?? 'API 回應失敗',
                    'response' => $responseData,
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

        } catch (\Throwable $th) {
            DB::rollBack();

            Log::error('Giveme Production B2C Error', [
                'invoice_id' => $invoice->id,
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'B2C 發票開立異常',
                'invoice_id' => $invoice->id,
                'error' => $th->getMessage(),
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * 開立 B2B 發票
     *
     * @param Invoice $invoice
     * @param int|null $orderId
     * @param string|null $orderCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function issueB2B(Invoice $invoice, $orderId = null, $orderCode = null)
    {
        DB::beginTransaction();

        try {
            $timeStamp = round(microtime(true) * 1000);
            $sign = strtoupper(md5($timeStamp . $this->account . $this->password));

            // 組裝商品明細
            $items = [];
            foreach ($invoice->invoiceItems as $item) {
                $items[] = [
                    'name' => $item->name,
                    'money' => (float)$item->price,
                    'number' => (int)$item->quantity,
                    'remark' => $item->remark ?? '',
                ];
            }

            // 組裝請求資料
            $requestData = [
                'timeStamp' => (string)$timeStamp,
                'uncode' => $this->taxId,
                'idno' => $this->account,
                'sign' => $sign,
                'customerName' => $invoice->buyer_name ?? '',
                'phone' => $invoice->tax_id_number,  // B2B 的 phone 是買方統編
                'datetime' => \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d'),
                'email' => $invoice->email ?? '',
                'taxState' => (string)($invoice->tax_state ?? 0),
                'totalFee' => (string)$invoice->total_amount,
                'amount' => (string)$invoice->tax_amount,
                'sales' => (string)($invoice->net_amount ?? ($invoice->total_amount - $invoice->tax_amount)),
                'taxType' => $this->mapTaxType($invoice->tax_type),
                'content' => $invoice->content ?? '商品銷售',
                'items' => $items,
            ];

            Log::info('Giveme Production B2B Request', [
                'invoice_id' => $invoice->id,
                'request' => $requestData,
            ]);

            // 發送請求
            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . '?action=addB2B', $requestData);

            $responseData = $response->json();

            Log::info('Giveme Production B2B Response', [
                'invoice_id' => $invoice->id,
                'status' => $response->status(),
                'response' => $responseData,
            ]);

            // 更新資料庫
            if (isset($responseData['success']) && $responseData['success'] === 'true') {
                $invoice->invoice_number = $responseData['code'];
                $invoice->api_request_data = json_encode($requestData);
                $invoice->api_response_data = json_encode($responseData);
                $invoice->giveme_status = '0';
                $invoice->save();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'B2B 發票開立成功',
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'response' => $responseData,
                ], 200, [], JSON_UNESCAPED_UNICODE);
            } else {
                // API 回應失敗
                $invoice->api_request_data = json_encode($requestData);
                $invoice->api_response_data = json_encode($responseData);
                $invoice->api_error = $responseData['msg'] ?? 'API 回應失敗';
                $invoice->save();

                DB::commit();

                return response()->json([
                    'success' => false,
                    'message' => 'B2B 發票開立失敗',
                    'invoice_id' => $invoice->id,
                    'error' => $responseData['msg'] ?? 'API 回應失敗',
                    'response' => $responseData,
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

        } catch (\Throwable $th) {
            DB::rollBack();

            Log::error('Giveme Production B2B Error', [
                'invoice_id' => $invoice->id,
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'B2B 發票開立異常',
                'invoice_id' => $invoice->id,
                'error' => $th->getMessage(),
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * 查詢發票
     *
     * POST /api/posv2/sales/invoice-issue/giveme/query
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function query(Request $request)
    {
        try {
            $request->validate([
                'invoice_id' => 'required|integer',
            ]);

            $invoiceId = $request->input('invoice_id');

            // 從資料庫讀取發票
            $invoice = Invoice::find($invoiceId);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => '找不到發票',
                    'invoice_id' => $invoiceId,
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            if (empty($invoice->invoice_number) || $invoice->invoice_number === 'PENDING') {
                return response()->json([
                    'success' => false,
                    'message' => '發票尚未開立',
                    'invoice_id' => $invoiceId,
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            $timeStamp = round(microtime(true) * 1000);
            $sign = strtoupper(md5($timeStamp . $this->account . $this->password));

            // 組裝請求資料
            $requestData = [
                'timeStamp' => (string)$timeStamp,
                'uncode' => $this->taxId,
                'idno' => $this->account,
                'sign' => $sign,
                'code' => $invoice->invoice_number,
            ];

            Log::info('Giveme Production Query Request', ['request' => $requestData]);

            // 發送請求
            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . '?action=query', $requestData);

            $responseData = $response->json();

            Log::info('Giveme Production Query Response', [
                'status' => $response->status(),
                'response' => $responseData,
            ]);

            return response()->json([
                'success' => true,
                'message' => '發票查詢成功',
                'invoice_id' => $invoice->id,
                'response' => $responseData,
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $th) {
            Log::error('Giveme Production Query Error', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return $this->sendJsonErrorResponse(
                data: ['error' => $th->getMessage()],
                status_code: 500
            );
        }
    }

    /**
     * 作廢發票
     *
     * POST /api/posv2/sales/invoice-issue/giveme/cancel
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'invoice_id' => 'required|integer',
                'reason' => 'required|string',
            ]);

            $invoiceId = $request->input('invoice_id');
            $reason = $request->input('reason');

            // 從資料庫讀取發票
            $invoice = Invoice::find($invoiceId);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => '找不到發票',
                    'invoice_id' => $invoiceId,
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            if (empty($invoice->invoice_number) || $invoice->invoice_number === 'PENDING') {
                return response()->json([
                    'success' => false,
                    'message' => '發票尚未開立，無法作廢',
                    'invoice_id' => $invoiceId,
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            if ($invoice->giveme_status === '1') {
                return response()->json([
                    'success' => false,
                    'message' => '發票已作廢',
                    'invoice_id' => $invoiceId,
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            $timeStamp = round(microtime(true) * 1000);
            $sign = strtoupper(md5($timeStamp . $this->account . $this->password));

            // 組裝請求資料
            $requestData = [
                'timeStamp' => (string)$timeStamp,
                'uncode' => $this->taxId,
                'idno' => $this->account,
                'sign' => $sign,
                'code' => $invoice->invoice_number,
                'remark' => $reason,
            ];

            Log::info('Giveme Production Cancel Request', ['request' => $requestData]);

            // 發送請求
            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . '?action=cancelInvoice', $requestData);

            $responseData = $response->json();

            Log::info('Giveme Production Cancel Response', [
                'status' => $response->status(),
                'response' => $responseData,
            ]);

            // 更新資料庫
            if (isset($responseData['success']) && $responseData['success'] === 'true') {
                $invoice->giveme_status = '1';
                $invoice->canceled_at = now();
                $invoice->cancel_reason = $reason;
                $invoice->status = 'canceled';
                $invoice->save();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => '發票作廢成功',
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'response' => $responseData,
                ], 200, [], JSON_UNESCAPED_UNICODE);
            } else {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => '發票作廢失敗',
                    'invoice_id' => $invoice->id,
                    'error' => $responseData['msg'] ?? 'API 回應失敗',
                    'response' => $responseData,
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

        } catch (\Throwable $th) {
            DB::rollBack();

            Log::error('Giveme Production Cancel Error', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return $this->sendJsonErrorResponse(
                data: ['error' => $th->getMessage()],
                status_code: 500
            );
        }
    }

    /**
     * 取得發票列印 URL
     *
     * GET /api/posv2/sales/invoice-issue/giveme/print-url/{invoice_number}
     *
     * @param string $invoiceNumber
     * @return \Illuminate\Http\JsonResponse
     */
    public function printUrl($invoiceNumber)
    {
        try {
            if (empty($invoiceNumber)) {
                return response()->json([
                    'success' => false,
                    'message' => '請提供發票號碼',
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            // 組裝列印 URL（使用正式環境統編）
            $printUrl = $this->apiUrl . '?action=invoicePrint&code=' . $invoiceNumber . '&uncode=' . $this->taxId;

            Log::info('Giveme Production Print URL', [
                'invoice_number' => $invoiceNumber,
                'print_url' => $printUrl,
            ]);

            return response()->json([
                'success' => true,
                'message' => '發票列印 URL',
                'invoice_number' => $invoiceNumber,
                'print_url' => $printUrl,
                'note' => '請在瀏覽器中開啟 print_url 查看發票列印頁面',
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $th) {
            Log::error('Giveme Production Print URL Error', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return $this->sendJsonErrorResponse(
                data: ['error' => $th->getMessage()],
                status_code: 500
            );
        }
    }

    /**
     * 轉換稅別類型
     *
     * @param mixed $taxType (Enum|string|null)
     * @return int
     */
    protected function mapTaxType($taxType): int
    {
        // 處理 Enum 物件
        if (is_object($taxType) && method_exists($taxType, 'value')) {
            $taxType = $taxType->value;
        }

        return match ($taxType) {
            'taxable' => 0,      // 應稅
            'zero_rate' => 1,    // 零稅率
            'exempt' => 2,       // 免稅
            'special' => 3,      // 特種稅
            'mixed' => 4,        // 混合稅
            default => 0,
        };
    }
}
