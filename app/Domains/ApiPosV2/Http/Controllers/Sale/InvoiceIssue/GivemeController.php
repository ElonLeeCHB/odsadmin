<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Sale\InvoiceIssue;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use App\Models\Sale\Invoice;
use App\Models\Sale\InvoiceGroup;

/**
 * Giveme 電子發票控制器
 *
 * 用途：發票開立流程（從資料庫讀取）
 * 特點：同時支援正式環境與測試環境
 *
 * 正式環境端點：
 *   - POST /issue, /query, /cancel, /picture
 *   - GET /print-url/{invoice_number}
 *
 * 測試環境端點（使用測試憑證）：
 *   - POST /test/issue, /test/query, /test/cancel, /test/picture
 *   - GET /test/print-url/{invoice_number}
 */

/*
  ✅ Giveme 電子發票路徑

  基礎路徑

  http://your-domain.com/api/posv2/sales/invoices/giveme

  可用端點（正式環境）

  | 方法   | 路徑                          | 說明                                     |
  |------|------------------------------|----------------------------------------|
  | POST | /issue                       | 開立發票（需提供 invoice_id, group_no）         |
  | POST | /query                       | 查詢發票（需提供 invoice_id）                    |
  | POST | /cancel                      | 作廢發票（需提供 invoice_id, reason）            |
  | POST | /picture                     | 取得發票圖片（需提供 invoice_id, group_no）       |
  | GET  | /print-url/{invoice_number}  | 取得發票列印 URL                              |

  可用端點（測試環境，使用測試憑證）

  | 方法   | 路徑                               | 說明                                  |
  |------|----------------------------------|-------------------------------------|
  | POST | /test/issue                      | 開立發票（測試）                           |
  | POST | /test/query                      | 查詢發票（測試）                           |
  | POST | /test/cancel                     | 作廢發票（測試）                           |
  | POST | /test/picture                    | 取得發票圖片（測試）                         |
  | GET  | /test/print-url/{invoice_number} | 取得發票列印 URL（測試）                     |

  完整 URL 範例

  # 1. 開立發票
  POST http://your-domain.com/api/posv2/sales/invoices/giveme/issue
  Body: {
    "invoice_id": 123,
    "group_no": 20250001
  }

  # 2. 查詢發票
  POST http://your-domain.com/api/posv2/sales/invoices/giveme/query
  Body: {
    "invoice_id": 123
  }

  # 3. 作廢發票
  POST http://your-domain.com/api/posv2/sales/invoices/giveme/cancel
  Body: {
    "invoice_id": 123,
    "reason": "客戶要求作廢"
  }

  # 4. 取得發票圖片
  POST http://your-domain.com/api/posv2/sales/invoices/giveme/picture
  Body: {
    "invoice_id": 123,
    "group_no": 20250001,
    "type": 1  // 1=發票證明聯+交易明細, 2=發票證明聯, 3=交易明細
  }

  # 5. 取得發票列印 URL
  GET http://your-domain.com/api/posv2/sales/invoices/giveme/print-url/JN80000776
*/

class GivemeController extends ApiPosController
{
    /**
     * API 基礎 URL
     */
    protected ?string $apiUrl = null;

    /**
     * 當前使用的憑證
     */
    protected array $credentials = [];

    // ========================================
    // 憑證取得方法
    // ========================================

    /**
     * 初始化 API URL
     */
    protected function initApiUrl(): void
    {
        if ($this->apiUrl === null) {
            $this->apiUrl = config('invoice.giveme.api_url');
        }
    }

    /**
     * 取得正式環境憑證
     */
    protected function getProductionCredentials(): array
    {
        return [
            'uncode' => config('invoice.giveme.uncode'),
            'account' => config('invoice.giveme.account'),
            'password' => config('invoice.giveme.password'),
        ];
    }

    /**
     * 取得測試環境憑證
     */
    protected function getTestCredentials(): array
    {
        return [
            'uncode' => config('invoice.test.uncode'),
            'account' => config('invoice.test.account'),
            'password' => config('invoice.test.password'),
        ];
    }

    /**
     * 產生簽章
     */
    protected function generateSignature(string $timeStamp, array $credentials): string
    {
        return strtoupper(md5($timeStamp . $credentials['account'] . $credentials['password']));
    }

    // ========================================
    // 正式環境端點
    // ========================================

    /**
     * 開立發票（正式環境）
     *
     * POST /api/posv2/sales/invoices/giveme/issue
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function issue(Request $request)
    {
        return $this->processIssue($request, $this->getProductionCredentials(), 'production');
    }

    /**
     * 查詢發票（正式環境）
     *
     * POST /api/posv2/sales/invoices/giveme/query
     */
    public function query(Request $request)
    {
        return $this->processQuery($request, $this->getProductionCredentials(), 'production');
    }

    /**
     * 作廢發票（正式環境）
     *
     * POST /api/posv2/sales/invoices/giveme/cancel
     */
    public function cancel(Request $request)
    {
        return $this->processCancel($request, $this->getProductionCredentials(), 'production');
    }

    /**
     * 發票圖片列印（正式環境）
     *
     * POST /api/posv2/sales/invoices/giveme/picture
     */
    public function picture(Request $request)
    {
        return $this->processPicture($request, $this->getProductionCredentials(), 'production');
    }

    /**
     * 取得發票列印 URL（正式環境）- 已棄用，建議使用 invoicePrint
     *
     * GET /api/posv2/sales/invoices/giveme/print-url/{invoice_number}
     *
     * @deprecated 請改用 invoicePrint，直接返回 HTML 頁面
     */
    public function printUrl($invoiceNumber)
    {
        return $this->processPrintUrl($invoiceNumber, $this->getProductionCredentials(), 'production');
    }

    /**
     * 發票列印（正式環境）- Giveme 文件 1.1.4
     *
     * GET /api/posv2/sales/invoices/giveme/invoicePrint/{invoice_number}
     *
     * 直接返回 Giveme 的發票列印 HTML 頁面
     */
    public function invoicePrint($invoiceNumber)
    {
        return $this->processInvoicePrint($invoiceNumber, $this->getProductionCredentials(), 'production');
    }

    /**
     * 取得發票圖片（正式環境）- GET 版本
     *
     * GET /api/posv2/sales/invoices/giveme/picture/{invoice_number}
     *
     * 直接用 invoice_number 取得發票圖片（預設 type=1）
     */
    public function pictureByNumber($invoiceNumber)
    {
        return $this->processPictureByNumber($invoiceNumber, $this->getProductionCredentials(), 'production');
    }

    // ========================================
    // 測試環境端點
    // ========================================

    /**
     * 開立發票（測試環境）
     *
     * POST /api/posv2/sales/invoices/giveme/test/issue
     */
    public function testIssue(Request $request)
    {
        return $this->processIssue($request, $this->getTestCredentials(), 'test');
    }

    /**
     * 查詢發票（測試環境）
     *
     * POST /api/posv2/sales/invoices/giveme/test/query
     */
    public function testQuery(Request $request)
    {
        return $this->processQuery($request, $this->getTestCredentials(), 'test');
    }

    /**
     * 作廢發票（測試環境）
     *
     * POST /api/posv2/sales/invoices/giveme/test/cancel
     */
    public function testCancel(Request $request)
    {
        return $this->processCancel($request, $this->getTestCredentials(), 'test');
    }

    /**
     * 發票圖片列印（測試環境）
     *
     * POST /api/posv2/sales/invoices/giveme/test/picture
     */
    public function testPicture(Request $request)
    {
        return $this->processPicture($request, $this->getTestCredentials(), 'test');
    }

    /**
     * 取得發票列印 URL（測試環境）- 已棄用，建議使用 testInvoicePrint
     *
     * GET /api/posv2/sales/invoices/giveme/test/print-url/{invoice_number}
     *
     * @deprecated 請改用 testInvoicePrint，直接返回 HTML 頁面
     */
    public function testPrintUrl($invoiceNumber)
    {
        return $this->processPrintUrl($invoiceNumber, $this->getTestCredentials(), 'test');
    }

    /**
     * 發票列印（測試環境）- Giveme 文件 1.1.4
     *
     * GET /api/posv2/sales/invoices/giveme/test/invoicePrint/{invoice_number}
     *
     * 直接返回 Giveme 的發票列印 HTML 頁面
     */
    public function testInvoicePrint($invoiceNumber)
    {
        return $this->processInvoicePrint($invoiceNumber, $this->getTestCredentials(), 'test');
    }

    /**
     * 取得發票圖片（測試環境）- GET 版本
     *
     * GET /api/posv2/sales/invoices/giveme/test/picture/{invoice_number}
     *
     * 直接用 invoice_number 取得發票圖片（預設 type=1）
     */
    public function testPictureByNumber($invoiceNumber)
    {
        return $this->processPictureByNumber($invoiceNumber, $this->getTestCredentials(), 'test');
    }

    // ========================================
    // 核心處理方法
    // ========================================

    /**
     * 處理開立發票
     *
     * @param Request $request
     * @param array $credentials
     * @param string $env
     * @return \Illuminate\Http\JsonResponse
     */
    protected function processIssue(Request $request, array $credentials, string $env)
    {
        $this->initApiUrl();
        $this->credentials = $credentials;

        try {
            // 驗證輸入
            $request->validate([
                'invoice_id' => 'required|integer',
                'group_no' => 'required|integer',
                'order_id' => 'nullable|integer',
                'order_code' => 'nullable|string',
            ]);

            $invoiceId = $request->input('invoice_id');
            $groupNo = $request->input('group_no');
            $orderId = $request->input('order_id');
            $orderCode = $request->input('order_code');

            // 檢查發票群組是否存在
            $invoiceGroup = InvoiceGroup::where('group_no', $groupNo)->first();

            if (!$invoiceGroup) {
                return response()->json([
                    'success' => false,
                    'message' => '找不到發票群組',
                    'group_no' => $groupNo,
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // 從資料庫讀取發票
            $invoice = Invoice::with('invoiceItems')->find($invoiceId);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => '找不到發票',
                    'invoice_id' => $invoiceId,
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // 檢查發票是否屬於該群組
            $invoiceBelongsToGroup = $invoiceGroup->invoices()
                ->where('invoices.id', $invoiceId)
                ->exists();

            if (!$invoiceBelongsToGroup) {
                return response()->json([
                    'success' => false,
                    'message' => '發票不屬於此群組',
                    'invoice_id' => $invoiceId,
                    'group_no' => $groupNo,
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            // 檢查發票是否已開立（允許 PENDING 或 PENDING_* 開頭的臨時號碼）
            if (!empty($invoice->invoice_number)
                && $invoice->invoice_number !== 'PENDING'
                && !str_starts_with($invoice->invoice_number, 'PENDING_')) {
                return response()->json([
                    'success' => false,
                    'message' => '發票已存在',
                    'data' => [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                    ],
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

            throw $th;
        }
    }

    /**
     * 開立 B2C 發票
     * Giveme 文件 1.1.1 B2C 發票新增介面
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
            $sign = $this->generateSignature((string)$timeStamp, $this->credentials);

            // 根據 tax_included 計算正確的金額
            // tax_included = 1: invoice_items.price 是含稅價，加總即為含稅總金額
            // tax_included = 0: invoice_items.price 是未稅價，需另外計算稅額
            $taxIncluded = (int)($invoice->tax_included ?? 0);

            // 從 invoice_items 計算項目加總
            $itemsTotal = $invoice->invoiceItems->sum(function ($item) {
                return (float)$item->price * (float)$item->quantity;
            });

            // 根據 tax_included 計算 totalFee（含稅總金額）
            if ($taxIncluded === 1) {
                // 含稅價：items 加總就是含稅總金額
                $totalFee = round($itemsTotal);
            } else {
                // 未稅價：items 加總是未稅金額，需加上稅額
                $totalFee = round($itemsTotal * 1.05);
            }

            // 組裝商品明細
            $items = [];
            foreach ($invoice->invoiceItems as $item) {
                $items[] = [
                    'name' => $item->name,
                    'money' => (int)round($item->price),  // 轉整數，機迷坊不接受帶小數的字串
                    'number' => (int)$item->quantity,
                    'remark' => $item->remark ?? '',
                ];
            }

            // 組裝請求資料（格式需與 GivemeDataController 一致）
            // 注意：金額欄位必須是不帶小數點的數值，機迷坊不接受 "5471.00" 這種格式
            $requestData = [
                'timeStamp' => (string)$timeStamp,
                'uncode' => $this->credentials['uncode'],
                'idno' => $this->credentials['account'],
                'sign' => $sign,
                'customerName' => $invoice->buyer_name ?? '',
                'phone' => '',
                'datetime' => \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d'),
                'email' => $invoice->email ?? '',
                'state' => $invoice->carrier_type === 'donation' ? '1' : '0',
                'taxType' => $this->mapTaxType($invoice->tax_type),
                'totalFee' => (int)$totalFee,
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

            // echo "<pre>", print_r($requestData, true), "</pre>"; exit;

            /*
            得到結果：
                <pre>Array
                (
                    [timeStamp] => 1764219862968
                    [uncode] => 95463108
                    [idno] => elonlee
                    [sign] => 52F41685C5074C1983E8BACB3997A131
                    [customerName] => 台湾科技股份有限公司
                    [datetime] => 2025-11-27
                    [email] => accounting@company.com
                    [state] => 0
                    [taxType] => 0
                    [totalFee] => 9000.00
                    [content] => 合并开立发票
                    [items] => Array
                        (
                            [0] => Array
                                (
                                    [name] => 冰粽礼盒A（大宗采购）
                                    [money] => 476
                                    [number] => 10
                                    [remark] => 未税单价
                                )

                            [1] => Array
                                (
                                    [name] => 冰粽礼盒B（大宗采购）
                                    [money] => 761
                                    [number] => 5
                                    [remark] => 未税单价
                                )

                        )

                )
                </pre>

            */

            // 發送請求（機迷坊 SSL 憑證有問題，需跳過驗證）
            $fullUrl = $this->apiUrl . '?action=addB2C';

            Log::info('Giveme Production B2C Request URL', [
                'apiUrl' => $this->apiUrl,
                'fullUrl' => $fullUrl,
            ]);

            try {
                $response = Http::timeout(30)
                    ->withoutVerifying()
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($fullUrl, $requestData);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::error('Giveme Production B2C Connection Error', [
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'B2C 發票開立失敗 - 連線錯誤',
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                    'api_url' => $fullUrl,
                ], 500, [], JSON_UNESCAPED_UNICODE);
            }

            $responseData = $response->json();
            $httpStatus = $response->status();
            $responseBody = $response->body();

            Log::info('Giveme Production B2C Response', [
                'invoice_id' => $invoice->id,
                'http_status' => $httpStatus,
                'response_body' => $responseBody,
                'response_json' => $responseData,
            ]);

            // 檢查 HTTP 請求是否失敗
            if ($response->failed() || $responseData === null) {
                $invoice->api_request_data = $requestData;
                $invoice->api_response_data = ['raw_body' => $responseBody];
                $invoice->api_error = "HTTP {$httpStatus}: " . ($responseBody ?: '無回應');
                $invoice->save();

                DB::commit();

                return response()->json([
                    'success' => false,
                    'message' => 'B2C 發票開立失敗 - HTTP 請求錯誤',
                    'invoice_id' => $invoice->id,
                    'http_status' => $httpStatus,
                    'error' => $responseBody ?: '無回應',
                    'api_url' => $this->apiUrl . '?action=addB2C',
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            // 更新資料庫
            if (isset($responseData['success']) && $responseData['success'] === 'true') {
                $invoice->invoice_number = $responseData['code'];
                $invoice->random_code = $responseData['randomCode'] ?? null;
                $invoice->api_request_data = $requestData;
                $invoice->api_response_data = $responseData;
                $invoice->status = 'issued';
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
                $invoice->api_request_data = $requestData;
                $invoice->api_response_data = $responseData;
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
     * Giveme 文件 1.1.2 B2B 發票新增介面
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
            $sign = $this->generateSignature((string)$timeStamp, $this->credentials);

            // 根據 tax_included 計算正確的金額
            // tax_included = 1: invoice_items.price 是含稅價，加總即為含稅總金額
            // tax_included = 0: invoice_items.price 是未稅價，需另外計算稅額
            $taxIncluded = (int)($invoice->tax_included ?? 0);

            // 從 invoice_items 計算項目加總
            $itemsTotal = $invoice->invoiceItems->sum(function ($item) {
                return (float)$item->price * (float)$item->quantity;
            });

            // 根據 tax_included 計算 totalFee, amount(稅額), sales(未稅金額)
            if ($taxIncluded === 1) {
                // 含稅價：items 加總就是含稅總金額
                $totalFee = round($itemsTotal);
                $sales = round($itemsTotal / 1.05);  // 未稅金額 = 含稅 / 1.05
                $amount = $totalFee - $sales;        // 稅額 = 含稅 - 未稅
            } else {
                // 未稅價：items 加總是未稅金額
                $sales = round($itemsTotal);
                $amount = round($itemsTotal * 0.05); // 稅額 = 未稅 * 5%
                $totalFee = $sales + $amount;        // 含稅總金額 = 未稅 + 稅額
            }

            // 組裝商品明細
            $items = [];
            foreach ($invoice->invoiceItems as $item) {
                $items[] = [
                    'name' => $item->name,
                    'money' => (int)round($item->price),  // 轉整數，機迷坊不接受帶小數的字串
                    'number' => (int)$item->quantity,
                    'remark' => $item->remark ?? '',
                ];
            }

            // 組裝請求資料
            // 注意：金額欄位必須是不帶小數點的字串，機迷坊不接受 "5471.00" 這種格式
            $requestData = [
                'timeStamp' => (string)$timeStamp,
                'uncode' => $this->credentials['uncode'],
                'idno' => $this->credentials['account'],
                'sign' => $sign,
                'customerName' => $invoice->buyer_name ?? '',
                'phone' => $invoice->tax_id_number,  // B2B 的 phone 是買方統編
                'datetime' => \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d'),
                'email' => $invoice->email ?? '',
                'taxState' => (string)($invoice->tax_state ?? 0),
                'totalFee' => (int)$totalFee,
                'amount' => (int)$amount,
                'sales' => (int)$sales,
                'taxType' => $this->mapTaxType($invoice->tax_type),
                'content' => $invoice->content ?? '商品銷售',
                'items' => $items,
            ];

            $fullUrl = $this->apiUrl . '?action=addB2B';

            Log::info('Giveme Production B2B Request', [
                'invoice_id' => $invoice->id,
                'apiUrl' => $this->apiUrl,
                'fullUrl' => $fullUrl,
                'request' => $requestData,
            ]);

            // 發送請求（機迷坊 SSL 憑證有問題，需跳過驗證）
            try {
                $response = Http::timeout(30)
                    ->withoutVerifying()
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($fullUrl, $requestData);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::error('Giveme Production B2B Connection Error', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'B2B 發票開立失敗 - 連線錯誤',
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                    'api_url' => $fullUrl,
                ], 500, [], JSON_UNESCAPED_UNICODE);
            }

            $responseData = $response->json();
            $httpStatus = $response->status();
            $responseBody = $response->body();

            Log::info('Giveme Production B2B Response', [
                'invoice_id' => $invoice->id,
                'http_status' => $httpStatus,
                'response_body' => $responseBody,
                'response_json' => $responseData,
            ]);

            // 檢查 HTTP 請求是否失敗
            if ($response->failed() || $responseData === null) {
                $invoice->api_request_data = $requestData;
                $invoice->api_response_data = ['raw_body' => $responseBody];
                $invoice->api_error = "HTTP {$httpStatus}: " . ($responseBody ?: '無回應');
                $invoice->save();

                DB::commit();

                return response()->json([
                    'success' => false,
                    'message' => 'B2B 發票開立失敗',
                    'invoice_id' => $invoice->id,
                    'error' => "HTTP {$httpStatus}: " . ($responseData['msg'] ?? $responseBody ?: '無回應'),
                    'response' => $responseData,
                    'api_url' => $fullUrl,
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            // 更新資料庫
            if (isset($responseData['success']) && $responseData['success'] === 'true') {
                $invoice->invoice_number = $responseData['code'];
                $invoice->api_request_data = $requestData;
                $invoice->api_response_data = $responseData;
                $invoice->status = 'issued';
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
                $invoice->api_request_data = $requestData;
                $invoice->api_response_data = $responseData;
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
     * 處理查詢發票
     *
     * @param Request $request
     * @param array $credentials
     * @param string $env
     * @return \Illuminate\Http\JsonResponse
     */
    protected function processQuery(Request $request, array $credentials, string $env)
    {
        $this->initApiUrl();
        $this->credentials = $credentials;

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
            $sign = $this->generateSignature((string)$timeStamp, $this->credentials);

            // 組裝請求資料
            $requestData = [
                'timeStamp' => (string)$timeStamp,
                'uncode' => $this->credentials['uncode'],
                'idno' => $this->credentials['account'],
                'sign' => $sign,
                'code' => $invoice->invoice_number,
            ];

            Log::info('Giveme Production Query Request', ['request' => $requestData]);

            // 發送請求（機迷坊 SSL 憑證有問題，需跳過驗證）
            $response = Http::timeout(30)
                ->withoutVerifying()
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

            throw $th;
        }
    }

    /**
     * 處理作廢發票
     *
     * @param Request $request
     * @param array $credentials
     * @param string $env
     * @return \Illuminate\Http\JsonResponse
     */
    protected function processCancel(Request $request, array $credentials, string $env)
    {
        $this->initApiUrl();
        $this->credentials = $credentials;

        //先將 request 的 reason 指定為 '客戶要求作廢'
        $request->merge(['reason' => '客戶要求作廢']);

        DB::beginTransaction();

        try {
            $request->validate([
                'invoice_id' => 'required|integer',
                'group_no' => 'required|integer',
                'reason' => 'required|string',
            ]);

            $invoiceId = $request->input('invoice_id');
            $groupNo = $request->input('group_no');
            $voidReason = $request->input('reason');

            // 檢查發票群組是否存在
            $invoiceGroup = InvoiceGroup::where('group_no', $groupNo)->first();

            if (!$invoiceGroup) {
                return response()->json([
                    'success' => false,
                    'message' => '找不到發票群組',
                    'data' => [
                        'group_no' => $groupNo,
                    ],
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // 從資料庫讀取發票
            $invoice = Invoice::find($invoiceId);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => '找不到發票',
                    'data' => [
                        'invoice_id' => $invoiceId,
                    ],
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // 檢查發票是否屬於該群組
            $invoiceBelongsToGroup = $invoiceGroup->invoices()
                ->where('invoices.id', $invoiceId)
                ->exists();

            if (!$invoiceBelongsToGroup) {
                return response()->json([
                    'success' => false,
                    'message' => '發票不屬於此群組',
                    'data' => [
                        'invoice_id' => $invoiceId,
                        'group_no' => $groupNo,
                    ],
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            if (empty($invoice->invoice_number) || $invoice->invoice_number === 'PENDING') {
                return response()->json([
                    'success' => false,
                    'message' => '發票尚未開立，無法作廢',
                    'data' => [
                        'invoice_id' => $invoiceId,
                    ],
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            if ($invoice->status === 'voided' || $invoice->status?->value === 'voided') {
                return response()->json([
                    'success' => false,
                    'message' => '發票已作廢',
                    'data' => [
                        'invoice_id' => $invoiceId,
                    ],
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            $timeStamp = round(microtime(true) * 1000);
            $sign = $this->generateSignature((string)$timeStamp, $this->credentials);

            // 組裝請求資料
            $requestData = [
                'timeStamp' => (string)$timeStamp,
                'uncode' => $this->credentials['uncode'],
                'idno' => $this->credentials['account'],
                'sign' => $sign,
                'code' => $invoice->invoice_number,
                'remark' => $voidReason,
            ];
            // echo "<pre>requestData = ", print_r($requestData, true), "</pre>";exit;
            Log::info("Giveme {$env} Cancel Request", ['request' => $requestData]);

            // 發送請求（機迷坊 SSL 憑證有問題，需跳過驗證）
            $response = Http::timeout(30)
                ->withoutVerifying()
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . '?action=cancelInvoice', $requestData);

            $responseData = $response->json();

            Log::info("Giveme {$env} Cancel Response", [
                'status' => $response->status(),
                'response' => $responseData,
            ]);

            // 更新資料庫
            if (isset($responseData['success']) && $responseData['success'] === 'true') {
                // 1. 更新發票狀態
                $invoice->voided_at = now();
                $invoice->void_reason = $voidReason;
                $invoice->status = 'voided';
                $invoice->save();

                // 2. 移除所有關聯，讓 InvoiceGroup 可重新開立發票
                // 取得關聯的 InvoiceGroup IDs（在 detach 前）
                $groupIds = $invoice->invoiceGroups()->pluck('invoice_groups.id')->toArray();

                // 移除 invoice_group_invoices 關聯
                // $invoice->invoiceGroups()->detach();

                // 3. 更新關聯的 InvoiceGroup 狀態為 pending
                if (!empty($groupIds)) {
                    InvoiceGroup::whereIn('id', $groupIds)->update(['invoice_status' => 'pending']);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => '發票作廢成功',
                    'data' => [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'removed_group_ids' => $groupIds,
                    ],
                ], 200, [], JSON_UNESCAPED_UNICODE);
            } else {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => $responseData['msg'] ?? '發票作廢失敗',
                    'data' => [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                    ],
                    'error_data' => [
                        'response' => $responseData,
                    ],
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

        } catch (\Throwable $th) {
            DB::rollBack();

            Log::error('Giveme Production Cancel Error', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            throw $th;
        }
    }

    /**
     * 處理取得發票列印 URL
     *
     * @param string $invoiceNumber
     * @param array $credentials
     * @param string $env
     * @return \Illuminate\Http\JsonResponse
     */
    protected function processPrintUrl($invoiceNumber, array $credentials, string $env)
    {
        $this->initApiUrl();
        $this->credentials = $credentials;

        try {
            if (empty($invoiceNumber)) {
                return response()->json([
                    'success' => false,
                    'message' => '請提供發票號碼',
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            // 組裝列印 URL
            $printUrl = $this->apiUrl . '?action=invoicePrint&code=' . $invoiceNumber . '&uncode=' . $this->credentials['uncode'];

            Log::info("Giveme {$env} Print URL", [
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
            Log::error("Giveme {$env} Print URL Error", [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            throw $th;
        }
    }

    /**
     * 處理發票列印（Giveme 文件 1.1.4）
     *
     * 直接呼叫 Giveme API 取得發票列印 HTML 頁面並返回
     *
     * @param string $invoiceNumber
     * @param array $credentials
     * @param string $env
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    protected function processInvoicePrint($invoiceNumber, array $credentials, string $env)
    {
        $this->initApiUrl();
        $this->credentials = $credentials;

        try {
            if (empty($invoiceNumber)) {
                return response()->json([
                    'success' => false,
                    'message' => '請提供發票號碼',
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            // 組裝列印 URL
            $printUrl = $this->apiUrl . '?action=invoicePrint&code=' . urlencode($invoiceNumber) . '&uncode=' . urlencode($this->credentials['uncode']);

            Log::info("Giveme {$env} Invoice Print", [
                'invoice_number' => $invoiceNumber,
                'print_url' => $printUrl,
            ]);

            // 呼叫 Giveme API 取得 HTML 頁面
            $response = Http::timeout(30)
                ->withoutVerifying()
                ->get($printUrl);

            $contentType = $response->header('Content-Type');
            $httpStatus = $response->status();

            Log::info("Giveme {$env} Invoice Print Response", [
                'invoice_number' => $invoiceNumber,
                'http_status' => $httpStatus,
                'content_type' => $contentType,
            ]);

            // 檢查是否為 JSON 錯誤回應
            if (str_contains($contentType ?? '', 'application/json')) {
                $responseData = $response->json();
                return response()->json([
                    'success' => false,
                    'message' => $responseData['msg'] ?? '發票列印失敗',
                    'data' => [
                        'invoice_number' => $invoiceNumber,
                    ],
                    'error_data' => [
                        'response' => $responseData,
                    ],
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            // 檢查 HTTP 請求是否失敗
            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => '發票列印失敗',
                    'data' => [
                        'invoice_number' => $invoiceNumber,
                        'http_status' => $httpStatus,
                    ],
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            // 成功：直接返回 HTML 頁面
            return response($response->body(), 200)
                ->header('Content-Type', $contentType ?? 'text/html; charset=utf-8');

        } catch (\Throwable $th) {
            Log::error("Giveme {$env} Invoice Print Error", [
                'invoice_number' => $invoiceNumber,
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            throw $th;
        }
    }

    /**
     * 處理發票圖片（GET 版本，直接用 invoice_number）
     *
     * 根據 Giveme 文件 1.1.5，取得發票圖片
     * 預設 type=1（發票證明聯+交易明細）
     *
     * @param string $invoiceNumber
     * @param array $credentials
     * @param string $env
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    protected function processPictureByNumber($invoiceNumber, array $credentials, string $env)
    {
        $this->initApiUrl();
        $this->credentials = $credentials;

        try {
            if (empty($invoiceNumber)) {
                return response()->json([
                    'success' => false,
                    'message' => '請提供發票號碼',
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            $timeStamp = round(microtime(true) * 1000);
            $sign = $this->generateSignature((string)$timeStamp, $this->credentials);

            // 組裝請求資料
            $requestData = [
                'timeStamp' => (string)$timeStamp,
                'uncode' => $this->credentials['uncode'],
                'idno' => $this->credentials['account'],
                'sign' => $sign,
                'code' => $invoiceNumber,
                'type' => '1',  // 預設 1=發票證明聯+交易明細
            ];

            Log::info("Giveme {$env} Picture By Number Request", [
                'invoice_number' => $invoiceNumber,
                'request' => $requestData,
            ]);

            // 發送請求取得圖片
            $response = Http::timeout(30)
                ->withoutVerifying()
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . '?action=picture', $requestData);

            $contentType = $response->header('Content-Type');
            $httpStatus = $response->status();

            Log::info("Giveme {$env} Picture By Number Response", [
                'invoice_number' => $invoiceNumber,
                'http_status' => $httpStatus,
                'content_type' => $contentType,
            ]);

            // 檢查是否為 JSON 錯誤回應
            if (str_contains($contentType ?? '', 'application/json')) {
                $responseData = $response->json();
                return response()->json([
                    'success' => false,
                    'message' => $responseData['msg'] ?? '取得發票圖片失敗',
                    'data' => [
                        'invoice_number' => $invoiceNumber,
                    ],
                    'error_data' => [
                        'response' => $responseData,
                    ],
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            // 檢查 HTTP 請求是否失敗
            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => '取得發票圖片失敗',
                    'data' => [
                        'invoice_number' => $invoiceNumber,
                        'http_status' => $httpStatus,
                    ],
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            // 成功：返回圖片串流
            return response($response->body(), 200)
                ->header('Content-Type', 'image/png')
                ->header('Content-Disposition', 'inline; filename="invoice_' . $invoiceNumber . '.png"');

        } catch (\Throwable $th) {
            Log::error("Giveme {$env} Picture By Number Error", [
                'invoice_number' => $invoiceNumber,
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            throw $th;
        }
    }

    /**
     * 處理發票圖片列印（POST 版本，需 invoice_id + group_no）
     *
     * 根據 Giveme 文件 1.1.5，取得發票圖片
     * type: 1=發票證明聯+交易明細, 2=發票證明聯, 3=交易明細
     *
     * @param Request $request
     * @param array $credentials
     * @param string $env
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    protected function processPicture(Request $request, array $credentials, string $env)
    {
        $this->initApiUrl();
        $this->credentials = $credentials;

        try {
            // 驗證輸入
            $request->validate([
                'invoice_id' => 'required|integer',
                'group_no' => 'required|integer',
                'type' => 'nullable|in:1,2,3',
            ]);

            $invoiceId = $request->input('invoice_id');
            $groupNo = $request->input('group_no');
            $type = $request->input('type', '1');  // 預設 1=發票證明聯+交易明細

            // 檢查發票群組是否存在
            $invoiceGroup = InvoiceGroup::where('group_no', $groupNo)->first();

            if (!$invoiceGroup) {
                return response()->json([
                    'success' => false,
                    'message' => '找不到發票群組',
                    'data' => [
                        'group_no' => $groupNo,
                    ],
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // 從資料庫讀取發票
            $invoice = Invoice::find($invoiceId);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => '找不到發票',
                    'data' => [
                        'invoice_id' => $invoiceId,
                    ],
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // 檢查發票是否屬於該群組
            $invoiceBelongsToGroup = $invoiceGroup->invoices()
                ->where('invoices.id', $invoiceId)
                ->exists();

            if (!$invoiceBelongsToGroup) {
                return response()->json([
                    'success' => false,
                    'message' => '發票不屬於此群組',
                    'data' => [
                        'invoice_id' => $invoiceId,
                        'group_no' => $groupNo,
                    ],
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            // 檢查發票是否已開立
            if (empty($invoice->invoice_number)
                || $invoice->invoice_number === 'PENDING'
                || str_starts_with($invoice->invoice_number, 'PENDING_')) {
                return response()->json([
                    'success' => false,
                    'message' => '發票尚未開立',
                    'data' => [
                        'invoice_id' => $invoiceId,
                    ],
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            $timeStamp = round(microtime(true) * 1000);
            $sign = $this->generateSignature((string)$timeStamp, $this->credentials);

            // 組裝請求資料
            $requestData = [
                'timeStamp' => (string)$timeStamp,
                'uncode' => $this->credentials['uncode'],
                'idno' => $this->credentials['account'],
                'sign' => $sign,
                'code' => $invoice->invoice_number,
                'type' => (string)$type,
            ];

            Log::info("Giveme {$env} Picture Request", [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'request' => $requestData,
            ]);

            // 發送請求取得圖片
            $response = Http::timeout(30)
                ->withoutVerifying()
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . '?action=picture', $requestData);

            $contentType = $response->header('Content-Type');

            Log::info("Giveme {$env} Picture Response", [
                'invoice_id' => $invoice->id,
                'status' => $response->status(),
                'content_type' => $contentType,
            ]);

            // 檢查是否為 JSON 錯誤回應
            if (str_contains($contentType ?? '', 'application/json')) {
                $responseData = $response->json();
                return response()->json([
                    'success' => false,
                    'message' => '取得發票圖片失敗',
                    'data' => [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'error' => $responseData['msg'] ?? 'API 回應失敗',
                    ],
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            // 檢查 HTTP 請求是否失敗
            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => '取得發票圖片失敗',
                    'data' => [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'http_status' => $response->status(),
                    ],
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            // 成功：返回圖片串流
            return response($response->body(), 200)
                ->header('Content-Type', 'image/png')
                ->header('Content-Disposition', 'inline; filename="invoice_' . $invoice->invoice_number . '.png"');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '參數驗證失敗',
                'errors' => $e->errors(),
            ], 422, [], JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $th) {
            Log::error("Giveme {$env} Picture Error", [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            throw $th;
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
