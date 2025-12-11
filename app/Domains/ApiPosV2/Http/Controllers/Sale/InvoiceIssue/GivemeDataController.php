<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Sale\InvoiceIssue;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;

/**
 * Giveme 電子發票 API 資料控制器
 *
 * 用途：直接對機迷坊 API 發送請求（前端傳送完整資料格式）
 * 特點：不操作資料庫，純 API 請求
 *
 * 憑證來源：
 * - 正式環境：config('invoice.giveme.xxx')
 * - 測試環境：config('invoice.test.xxx')
 */

/*
  ✅ Giveme 電子發票 API 資料請求路徑

  基礎路徑

  正式環境：/api/posv2/sales/invoices/giveme/data
  測試環境：/api/posv2/sales/invoices/giveme/data/test

  可用端點

  | 方法   | 正式環境路徑      | 測試環境路徑           | 說明                                  |
  |------|----------------|---------------------|-------------------------------------|
  | GET  | /config        | /test/config        | 查看當前環境設定                        |
  | GET  | /signature     | /test/signature     | 取得簽章（測試用）                       |
  | POST | /b2c           | /test/b2c           | B2C 發票開立（傳完整資料）                |
  | POST | /b2b           | /test/b2b           | B2B 發票開立（傳完整資料）                |
  | POST | /query         | /test/query         | 發票查詢（需提供 invoice_number）        |
  | POST | /cancel        | /test/cancel        | 發票作廢（需提供 invoice_number, reason）|
  | GET  | /print         | /test/print         | 發票列印（需提供 code 參數）              |
  | POST | /picture       | /test/picture       | 發票圖片（需提供 code, type）            |

  完整 URL 範例

  # 正式環境
  GET  /api/posv2/sales/invoices/giveme/data/config
  GET  /api/posv2/sales/invoices/giveme/data/signature
  POST /api/posv2/sales/invoices/giveme/data/b2c
  POST /api/posv2/sales/invoices/giveme/data/b2b
  POST /api/posv2/sales/invoices/giveme/data/query         Body: { "invoice_number": "AB12345678" }
  POST /api/posv2/sales/invoices/giveme/data/cancel        Body: { "invoice_number": "AB12345678", "reason": "作廢原因" }
  GET  /api/posv2/sales/invoices/giveme/data/print?code=AB12345678
  POST /api/posv2/sales/invoices/giveme/data/picture       Body: { "code": "AB12345678", "type": "1" }

  # 測試環境
  GET  /api/posv2/sales/invoices/giveme/data/test/config
  GET  /api/posv2/sales/invoices/giveme/data/test/signature
  POST /api/posv2/sales/invoices/giveme/data/test/b2c
  POST /api/posv2/sales/invoices/giveme/data/test/b2b
  POST /api/posv2/sales/invoices/giveme/data/test/query    Body: { "invoice_number": "AB12345678" }
  POST /api/posv2/sales/invoices/giveme/data/test/cancel   Body: { "invoice_number": "AB12345678", "reason": "測試作廢" }
  GET  /api/posv2/sales/invoices/giveme/data/test/print?code=AB12345678
  POST /api/posv2/sales/invoices/giveme/data/test/picture  Body: { "code": "AB12345678", "type": "1" }

  picture type: 1-證明聯+明細, 2-證明聯, 3-明細
*/

/* json 範例
  {
    "customerName": "測試公司",
    "phone": "/XYZ5678",
    "datetime": "2025-10-24",
    "email": "test@company.com",
    "state": "0",
    "taxType": 0,
    "totalFee": "550",
    "content": "月結帳單",
    "items": [
      {
        "name": "珍珠奶茶",
        "money": 200,
        "number": 2,
        "remark": ""
      },
      {
        "name": "雞排",
        "money": 150,
        "number": 1,
        "remark": "不辣"
      }
    ]
  }
*/

class GivemeDataController extends ApiPosController
{
    /**
     * API 基礎 URL
     */
    protected string $apiUrl;

    /**
     * 建構子
     */
    public function __construct()
    {
        parent::__construct();
        $this->apiUrl = config('invoice.giveme.api_url');
    }

    // ========================================
    // 憑證取得方法
    // ========================================

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

    public function b2c(Request $request)
    {
        return $this->processB2C($request, $this->getProductionCredentials(), 'production');
    }

    public function b2b(Request $request)
    {
        return $this->processB2B($request, $this->getProductionCredentials(), 'production');
    }

    public function query(Request $request)
    {
        return $this->processQuery($request, $this->getProductionCredentials(), 'production');
    }

    public function cancel(Request $request)
    {
        return $this->processCancel($request, $this->getProductionCredentials(), 'production');
    }

    public function print(Request $request)
    {
        return $this->processPrint($request, $this->getProductionCredentials(), 'production');
    }

    public function picture(Request $request)
    {
        return $this->processPicture($request, $this->getProductionCredentials(), 'production');
    }

    public function config()
    {
        return $this->showConfig($this->getProductionCredentials(), 'production');
    }

    public function signature()
    {
        return $this->showSignature($this->getProductionCredentials(), 'production');
    }

    // ========================================
    // 測試環境端點
    // ========================================

    public function testB2c(Request $request)
    {
        return $this->processB2C($request, $this->getTestCredentials(), 'test');
    }

    public function testB2b(Request $request)
    {
        return $this->processB2B($request, $this->getTestCredentials(), 'test');
    }

    public function testQuery(Request $request)
    {
        return $this->processQuery($request, $this->getTestCredentials(), 'test');
    }

    public function testCancel(Request $request)
    {
        return $this->processCancel($request, $this->getTestCredentials(), 'test');
    }

    public function testPrint(Request $request)
    {
        return $this->processPrint($request, $this->getTestCredentials(), 'test');
    }

    public function testPicture(Request $request)
    {
        return $this->processPicture($request, $this->getTestCredentials(), 'test');
    }

    public function testConfig()
    {
        return $this->showConfig($this->getTestCredentials(), 'test');
    }

    public function testSignature()
    {
        return $this->showSignature($this->getTestCredentials(), 'test');
    }

    // ========================================
    // 共用邏輯
    // ========================================

    /**
     * 1.1.1 B2C 發票新增介面
     * 處理 B2C 發票開立
     */
    protected function processB2C(Request $request, array $credentials, string $env)
    {
        try {
            $data = $request->all();
            $timeStamp = (string) round(microtime(true) * 1000);
            $sign = $this->generateSignature($timeStamp, $credentials);

            $requestData = [
                'timeStamp' => $timeStamp,
                'uncode' => $credentials['uncode'],
                'idno' => $credentials['account'],
                'sign' => $sign,
                'customerName' => $data['customerName'] ?? '',
                'phone' => $data['phone'] ?? '',
                'datetime' => $data['datetime'] ?? date('Y-m-d'),
                'email' => $data['email'] ?? '',
                'state' => $data['state'] ?? '0',
                'taxType' => $data['taxType'] ?? 0,
                'totalFee' => $data['totalFee'] ?? '0',
                'content' => $data['content'] ?? '',
                'items' => $data['items'] ?? [],
            ];

            Log::info("Giveme B2C Request [{$env}]", ['request' => $requestData]);

            $response = Http::timeout(30)
                ->withoutVerifying()
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . '?action=addB2C', $requestData);

            $responseData = $response->json();

            Log::info("Giveme B2C Response [{$env}]", [
                'status' => $response->status(),
                'response' => $responseData,
            ]);

            // 判斷機迷坊 API 回應是否成功
            $apiSuccess = isset($responseData['success']) && $responseData['success'] === 'true';
            $apiMessage = $responseData['msg'] ?? ($apiSuccess ? '開立成功' : 'API 回應失敗');

            // 處理 HTTP 非 200 回應
            if ($response->status() !== 200) {
                $apiSuccess = false;
                $apiMessage = "HTTP {$response->status()}: API 請求失敗";
            }

            return response()->json([
                'success' => $apiSuccess,
                'message' => $apiMessage,
                'env' => $env,
                'invoice_number' => $apiSuccess ? ($responseData['code'] ?? null) : null,
                'request' => $requestData,
                'response' => $responseData,
                'http_status' => $response->status(),
            ], $apiSuccess ? 200 : 400, [], JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $th) {
            Log::error("Giveme B2C Error [{$env}]", [
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
     * 1.1.2 B2B 發票新增介面
     * 處理 B2B 發票開立
     */
    protected function processB2B(Request $request, array $credentials, string $env)
    {
        try {
            $data = $request->all();
            $timeStamp = (string) round(microtime(true) * 1000);
            $sign = $this->generateSignature($timeStamp, $credentials);

            $requestData = [
                'timeStamp' => $timeStamp,
                'uncode' => $credentials['uncode'],
                'idno' => $credentials['account'],
                'sign' => $sign,
                'customerName' => $data['customerName'] ?? '',
                'phone' => $data['phone'] ?? '',
                'datetime' => $data['datetime'] ?? date('Y-m-d'),
                'email' => $data['email'] ?? '',
                'taxState' => $data['taxState'] ?? '0',
                'totalFee' => $data['totalFee'] ?? '0',
                'amount' => $data['amount'] ?? '0',
                'sales' => $data['sales'] ?? '0',
                'taxType' => $data['taxType'] ?? 0,
                'content' => $data['content'] ?? '',
                'items' => $data['items'] ?? [],
            ];

            Log::info("Giveme B2B Request [{$env}]", ['request' => $requestData]);

            $response = Http::timeout(30)
                ->withoutVerifying()
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . '?action=addB2B', $requestData);

            $responseData = $response->json();

            Log::info("Giveme B2B Response [{$env}]", [
                'status' => $response->status(),
                'response' => $responseData,
            ]);

            // 判斷機迷坊 API 回應是否成功
            $apiSuccess = isset($responseData['success']) && $responseData['success'] === 'true';
            $apiMessage = $responseData['msg'] ?? ($apiSuccess ? '開立成功' : 'API 回應失敗');

            // 處理 HTTP 404 等非正常回應
            if ($response->status() !== 200) {
                $apiSuccess = false;
                $apiMessage = "HTTP {$response->status()}: API 請求失敗";
            }

            return response()->json([
                'success' => $apiSuccess,
                'message' => $apiMessage,
                'env' => $env,
                'invoice_number' => $apiSuccess ? ($responseData['code'] ?? null) : null,
                'request' => $requestData,
                'response' => $responseData,
                'http_status' => $response->status(),
            ], $apiSuccess ? 200 : 400, [], JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $th) {
            Log::error("Giveme B2B Error [{$env}]", [
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
     * 1.1.3 發票查詢介面
     * 處理發票查詢
     */
    protected function processQuery(Request $request, array $credentials, string $env)
    {
        try {
            $invoiceNumber = $request->input('invoice_number', '');

            if (empty($invoiceNumber)) {
                return response()->json([
                    'success' => false,
                    'message' => '請提供發票號碼',
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            $timeStamp = (string) round(microtime(true) * 1000);
            $sign = $this->generateSignature($timeStamp, $credentials);

            $requestData = [
                'timeStamp' => $timeStamp,
                'uncode' => $credentials['uncode'],
                'idno' => $credentials['account'],
                'sign' => $sign,
                'code' => $invoiceNumber,
            ];

            Log::info("Giveme Query Request [{$env}]", ['request' => $requestData]);

            $response = Http::timeout(30)
                ->withoutVerifying()
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . '?action=query', $requestData);

            $responseData = $response->json();

            Log::info("Giveme Query Response [{$env}]", [
                'status' => $response->status(),
                'response' => $responseData,
            ]);

            // 判斷機迷坊 API 回應是否成功
            $apiSuccess = isset($responseData['success']) && $responseData['success'] === 'true';
            $apiMessage = $responseData['msg'] ?? ($apiSuccess ? '查詢成功' : 'API 回應失敗');

            if ($response->status() !== 200) {
                $apiSuccess = false;
                $apiMessage = "HTTP {$response->status()}: API 請求失敗";
            }

            return response()->json([
                'success' => $apiSuccess,
                'message' => $apiMessage,
                'env' => $env,
                'request' => $requestData,
                'response' => $responseData,
                'http_status' => $response->status(),
            ], $apiSuccess ? 200 : 400, [], JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $th) {
            Log::error("Giveme Query Error [{$env}]", [
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
     * 1.1.6 發票作廢介面
     * 處理發票作廢
     */
    protected function processCancel(Request $request, array $credentials, string $env)
    {
        try {
            $code = $request->input('code', '');
            $reason = $request->input('reason', '作廢');

            if (empty($code)) {
                return response()->json([
                    'success' => false,
                    'message' => '請提供發票號碼',
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            $timeStamp = (string) round(microtime(true) * 1000);
            $sign = $this->generateSignature($timeStamp, $credentials);

            $requestData = [
                'timeStamp' => $timeStamp,
                'uncode' => $credentials['uncode'],
                'idno' => $credentials['account'],
                'sign' => $sign,
                'code' => $code,
                'remark' => $reason,
            ];

            Log::info("Giveme Cancel Request [{$env}]", ['request' => $requestData]);

            $response = Http::timeout(30)
                ->withoutVerifying()
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . '?action=cancelInvoice', $requestData);

            $responseData = $response->json();

            Log::info("Giveme Cancel Response [{$env}]", [
                'status' => $response->status(),
                'response' => $responseData,
            ]);

            // 判斷機迷坊 API 回應是否成功
            $apiSuccess = isset($responseData['success']) && $responseData['success'] === 'true';
            $apiMessage = $responseData['msg'] ?? ($apiSuccess ? '作廢成功' : 'API 回應失敗');

            if ($response->status() !== 200) {
                $apiSuccess = false;
                $apiMessage = "HTTP {$response->status()}: API 請求失敗";
            }

            return response()->json([
                'success' => $apiSuccess,
                'message' => $apiMessage,
                'env' => $env,
                'request' => $requestData,
                'response' => $responseData,
                'http_status' => $response->status(),
            ], $apiSuccess ? 200 : 400, [], JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $th) {
            Log::error("Giveme Cancel Error [{$env}]", [
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
     * 處理發票列印
     */
    protected function processPrint(Request $request, array $credentials, string $env)
    {
        try {
            $invoiceNumber = $request->input('code', '');

            if (empty($invoiceNumber)) {
                return response()->json([
                    'success' => false,
                    'message' => '請提供發票號碼 (code)',
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            $printUrl = $this->apiUrl . '?action=invoicePrint&code=' . urlencode($invoiceNumber) . '&uncode=' . urlencode($credentials['uncode']);

            Log::info("Giveme Print Request [{$env}]", [
                'invoice_number' => $invoiceNumber,
                'print_url' => $printUrl,
            ]);

            $response = Http::timeout(30)
                ->withoutVerifying()
                ->get($printUrl);

            $contentType = $response->header('Content-Type');

            if (strpos($contentType, 'application/json') !== false) {
                $responseData = $response->json();

                return response()->json([
                    'success' => true,
                    'message' => "發票列印 [{$env}] (JSON 回應)",
                    'env' => $env,
                    'print_url' => $printUrl,
                    'response' => $responseData,
                    'http_status' => $response->status(),
                ], 200, [], JSON_UNESCAPED_UNICODE);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => "發票列印 [{$env}] (HTML 回應)",
                    'env' => $env,
                    'print_url' => $printUrl,
                    'note' => '請直接在瀏覽器中開啟 print_url 查看發票列印頁面',
                    'http_status' => $response->status(),
                    'content_type' => $contentType,
                ], 200, [], JSON_UNESCAPED_UNICODE);
            }

        } catch (\Throwable $th) {
            Log::error("Giveme Print Error [{$env}]", [
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
     * 處理發票圖片列印
     */
    protected function processPicture(Request $request, array $credentials, string $env)
    {
        try {
            $invoiceNumber = $request->input('code', '');
            $type = $request->input('type', '1');

            if (empty($invoiceNumber)) {
                return response()->json([
                    'success' => false,
                    'message' => '請提供發票號碼 (code)',
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            if (!in_array($type, ['1', '2', '3'])) {
                return response()->json([
                    'success' => false,
                    'message' => '圖片類型錯誤，請選擇 1-證明聯+明細, 2-證明聯, 3-明細',
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            $timeStamp = (string) round(microtime(true) * 1000);
            $sign = $this->generateSignature($timeStamp, $credentials);

            $requestData = [
                'timeStamp' => $timeStamp,
                'uncode' => $credentials['uncode'],
                'idno' => $credentials['account'],
                'sign' => $sign,
                'code' => $invoiceNumber,
                'type' => $type,
            ];

            Log::info("Giveme Picture Request [{$env}]", ['request' => $requestData]);

            $response = Http::timeout(30)
                ->withoutVerifying()
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl . '?action=picture', $requestData);

            $contentType = $response->header('Content-Type');

            if (strpos($contentType, 'application/json') !== false) {
                $responseData = $response->json();

                return response()->json([
                    'success' => $responseData['success'] ?? false,
                    'message' => "發票圖片 [{$env}] (錯誤回應)",
                    'env' => $env,
                    'request' => $requestData,
                    'response' => $responseData,
                    'http_status' => $response->status(),
                ], 200, [], JSON_UNESCAPED_UNICODE);
            } else {
                $imageBase64 = base64_encode($response->body());

                return response()->json([
                    'success' => true,
                    'message' => "發票圖片 [{$env}] (成功)",
                    'env' => $env,
                    'request' => $requestData,
                    'http_status' => $response->status(),
                    'content_type' => $contentType,
                    'image_base64' => $imageBase64,
                    'note' => '請將 image_base64 解碼後顯示或儲存為圖片',
                ], 200, [], JSON_UNESCAPED_UNICODE);
            }

        } catch (\Throwable $th) {
            Log::error("Giveme Picture Error [{$env}]", [
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
     * 顯示設定
     */
    protected function showConfig(array $credentials, string $env)
    {
        return response()->json([
            'success' => true,
            'message' => "Giveme API 設定 [{$env}]",
            'env' => $env,
            'config' => [
                'api_url' => $this->apiUrl,
                'uncode' => $credentials['uncode'],
                'account' => $credentials['account'],
                'password' => str_repeat('*', strlen($credentials['password'])),
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * 顯示簽章
     */
    protected function showSignature(array $credentials, string $env)
    {
        $timeStamp = (string) round(microtime(true) * 1000);
        $signString = $timeStamp . $credentials['account'] . $credentials['password'];
        $sign = strtoupper(md5($signString));

        return response()->json([
            'success' => true,
            'message' => "簽章算法 [{$env}]",
            'env' => $env,
            'data' => [
                'timeStamp' => $timeStamp,
                'uncode' => $credentials['uncode'],
                'account' => $credentials['account'],
                'sign_string' => $signString,
                'sign' => $sign,
            ],
            'note' => '簽章有效期 5 分鐘',
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
