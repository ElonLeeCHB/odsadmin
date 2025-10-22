<?php

namespace App\Repositories\Eloquent\Sale;

use App\Repositories\Eloquent\Repository;
use App\Helpers\Classes\DateHelper;
use App\Helpers\Classes\OrmHelper;
use App\Models\Sale\Order;
use App\Models\Sale\OrderProduct;
use App\Models\Sale\OrderProductOption;
use App\Models\Setting\Setting;
use App\Models\Inventory\Bom;
use App\Models\Inventory\BomProduct;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 訂單轉備料 Repository
 *
 * 負責將訂單資料轉換為備料表統計資料
 *
 * 訂單異動後，將送達日期 delivery_date 寫入 settings 資料表 setting_key='sale_order_queued_delivery_date
 * 預設行為：每20分鐘執行一次排程。讀取 sale_order_queued_delivery_date，逐一執行每一天。寫入內建快取。內含創建時間的欄位。
 * 如果20分鐘之內有執行過，則跳過，等待下一輪執行。
 *
 * 後台備料表頁，查詢日期時，抓取快取。如果沒有快取就不顯示資料。因為排程本就應該執行，本就應該有快取。不管是每20分鐘或是每小時。
 * 但是可以使用"更新"按鈕做即時更新，立刻更新快取。
 *
 * 區間查詢的時候，抓取各日快取。如果沒有快取就不顯示資料。因為排程本就應該執行，本就應該有快取。不管是每20分鐘或是每小時。
 */
class OrderDailyRequisitionRepository
{
    // ==================== 配置常數 ====================

    // 列印分類 ID
    const PRINTING_CATEGORY_SINGLE_ITEM = 1494; // 單點無選項

    // 套餐類別
    const PRINTING_CATEGORY_LUMPIA_BENTO = 1471; // 潤餅便當
    const PRINTING_CATEGORY_GUABAO_BENTO = 1472; // 刈包便當
    const PRINTING_CATEGORY_LUMPIA_LUNCHBOX = 1473; // 潤餅盒餐
    const PRINTING_CATEGORY_GUABAO_LUNCHBOX = 1474; // 刈包盒餐
    const PRINTING_CATEGORY_OIL_RICE_BOX = 1475; // 油飯盒
    const PRINTING_CATEGORY_CUSTOM_BENTO = 1477; // 客製便當
    const PRINTING_CATEGORY_CUSTOM_LUNCHBOX = 1478; // 客製盒餐

    // 商品 ID
    const PRODUCT_EXCELLENT_OIL_RICE = 1737; // 極品油飯
    const PRODUCT_CHEF_OIL_RICE = 1036; // 廚娘油飯
    const PRODUCT_ALL_VEGETARIAN_SMALL_GUABAO = 1688; // 全素小刈包
    const PRODUCT_DAIRY_VEGETARIAN_SMALL_GUABAO = 1689; // 奶素小刈包
    const PRODUCT_BRAISED_FOOD_UNIT = 1804; // 滷味個
    const PRODUCT_VEGETABLE_SMALL_GUABAO = 1664; // 鮮蔬小刈包
    const PRODUCT_SPRING_ROLL = 1661; // 春捲

    // 選項值 ID
    const OPTION_VALUE_BRAISED_SMALL = 1202; // 滷味小
    const OPTION_VALUE_BRAISED_MEDIUM = 1203; // 滷味中
    const OPTION_VALUE_BRAISED_LARGE = 1204; // 滷味大

    // 時段分界點
    const TIME_CUT_OFF = '1300'; // 13:00

    // 快取時間（分鐘）
    const CACHE_EXPIRE_MINUTES = 60;
    const CACHE_RETENTION_DAYS = 180;

    // ==================== 公開方法 ====================

    /**
     * 獲取指定日期的備料統計資料
     *
     * @param string $required_date 需求日期
     * @param int $force_update 是否強制更新快取 (0=否, 1=是)
     * @param bool $is_return 是否返回結果
     * @return array|null
     */
    public function getStatisticsByDate($required_date, $force_update = 0, $is_return = true)
    {
        $required_date = Carbon::parse($required_date)->format('Y-m-d');
        $cache_key = 'sale_order_requisition_date_' . $required_date;

        // 取得快取
        $statistics = cache()->get($cache_key);

        // 判斷是否需要重新產生快取
        if ($this->shouldRefreshCache($statistics, $force_update)) {
            cache()->forget($cache_key);

            $statistics = cache()->remember(
                $cache_key,
                60 * 24 * self::CACHE_RETENTION_DAYS,
                fn() => $this->calculateByDate($required_date)
            );
        }

        if ($is_return) {
            return $statistics;
        }
    }

    /**
     * 計算指定日期的備料統計資料
     *
     * @param string $required_date_ymd 需求日期 (Y-m-d)
     * @return array
     */
    public function calculateByDate($required_date_ymd)
    {
        // 驗證日期
        $requiredDateRawSql = DateHelper::parseDateToSqlWhere('delivery_date', $required_date_ymd);
        if (empty($requiredDateRawSql)) {
            return [];
        }

        // 獲取配置
        $config = $this->getConfiguration();

        // 獲取訂單
        $orders = $this->fetchOrders($requiredDateRawSql, $config['sales_orders_to_be_prepared_status']);
        if ($orders->isEmpty()) {
            return [];
        }

        // 處理訂單，建立訂單列表
        $order_list = $this->buildOrderList($orders, $config);

        // 排序訂單列表
        $order_list = $this->sortOrderList($order_list);

        // 累加統計（全日、上午、下午）
        $statistics = $this->accumulateStatistics($order_list, $config);

        // 計算總計
        $totals = $this->calculateTotals($orders);

        // 組合結果
        return [
            'order_list' => $order_list,
            'allDay' => $statistics['allDay'] ?? [],
            'allDay_sgb' => $statistics['allDay_sgb'] ?? 0,
            'allDay_bgb' => $statistics['allDay_bgb'] ?? 0,
            'allDay_6in' => $statistics['allDay_6in'] ?? 0,
            'allDay_sr' => $statistics['allDay_sr'] ?? 0,
            'am' => $statistics['am'] ?? [],
            'am_sgb' => $statistics['am_sgb'] ?? 0,
            'am_bgb' => $statistics['am_bgb'] ?? 0,
            'am_6in' => $statistics['am_6in'] ?? 0,
            'am_sr' => $statistics['am_sr'] ?? 0,
            'pm' => $statistics['pm'] ?? [],
            'pm_sgb' => $statistics['pm_sgb'] ?? 0,
            'pm_bgb' => $statistics['pm_bgb'] ?? 0,
            'pm_6in' => $statistics['pm_6in'] ?? 0,
            'pm_sr' => $statistics['pm_sr'] ?? 0,
            'info' => array_merge($totals, ['required_date_ymd' => $required_date_ymd]),
            'sales_ingredients_table_items' => $config['sales_ingredients_table_items'],
            'cache_created_at' => now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 根據商品 ID 獲取 BOM 項目
     *
     * @param int $product_id
     * @return array
     */
    public function getBomItemsByProductId($product_id)
    {
        $bom = Bom::query()
            ->where('product_id', $product_id)
            ->where('is_active', 1)
            ->whereDate('effective_date', '<', DB::raw('CURDATE()'))
            ->first();

        if ($bom) {
            $bom->load('bomProducts.translation');
            return $bom->bomProducts ?? [];
        }

        return [];
    }

    // ==================== 私有方法 - 配置與初始化 ====================

    /**
     * 判斷是否需要刷新快取
     */
    private function shouldRefreshCache($statistics, $force_update)
    {
        if ($force_update) {
            return true;
        }

        if (!$statistics || !isset($statistics['cache_created_at'])) {
            return true;
        }

        $cache_age_minutes = Carbon::parse($statistics['cache_created_at'])->diffInMinutes(now());
        return $cache_age_minutes > self::CACHE_EXPIRE_MINUTES;
    }

    /**
     * 獲取配置資料
     */
    private function getConfiguration()
    {
        // 一次查詢所有需要的設定
        $settings = Setting::whereIn('setting_key', [
            'sales_ingredients_table_items',
            'sales_orders_to_be_prepared_status',
            'sales_wrap_map',
        ])->get()->keyBy('setting_key');

        // 取得 sales_wrap_map 資料
        $sales_wrap_map = $settings['sales_wrap_map']->setting_value ?? [];

        return [
            'sales_ingredients_table_items' => $settings['sales_ingredients_table_items']->setting_value ?? [],
            'sales_orders_to_be_prepared_status' => $settings['sales_orders_to_be_prepared_status']->setting_value ?? [],
            'sales_wrap_map' => $sales_wrap_map,
            'wrap_ids_needing_halving' => array_keys($sales_wrap_map),
            'big_guabao_ids' => [1809, 1810, 1811, 1812, 1813, 1814, 1838, 1839, 1840],
            'small_guabao_ids' => [1664, 1665, 1666, 1667, 1668, 1669, 1672, 1688, 1689],
            'lumpia6in_ids' => [1010, 1011, 1012, 1013, 1014, 1015, 1056, 1058, 1663],
            'spring_roll_id' => self::PRODUCT_SPRING_ROLL,
            'braisedfood_option_value_ids' => [
                self::OPTION_VALUE_BRAISED_SMALL,
                self::OPTION_VALUE_BRAISED_MEDIUM,
                self::OPTION_VALUE_BRAISED_LARGE,
            ],
        ];
    }

    // ==================== 私有方法 - 資料獲取 ====================

    /**
     * 獲取訂單資料
     */
    private function fetchOrders($requiredDateRawSql, $preparedStatusCodes)
    {
        $query = Order::query();

        $query->select([
            'id', 'code', 'location_id', 'delivery_date', 'delivery_time_range',
            'personal_name', 'shipping_road', 'shipping_road_abbr', 'shipping_method', 'status_code'
        ]);

        $query->whereIn('status_code', $preparedStatusCodes);
        $query->whereRaw($requiredDateRawSql);

        $query->with([
            'orderProducts' => function ($query) {
                $query->select(['id', 'order_id', 'product_id', 'name', 'price', 'quantity', 'sort_order'])
                    ->with([
                        'orderProductOptions' => function ($query) {
                            $query->select([
                                'id', 'order_id', 'order_product_id', 'name', 'value',
                                'quantity', 'option_id', 'option_value_id', 'map_product_id'
                            ]);
                        },
                        'productPosCategories',
                    ]);
            }
        ]);

        return $query->get();
    }

    // ==================== 私有方法 - 訂單處理 ====================

    /**
     * 建立訂單列表
     */
    private function buildOrderList($orders, $config)
    {
        $order_list = [];

        foreach ($orders as $order) {
            $order_data = $this->initializeOrderData($order);

            foreach ($order->orderProducts as $orderProduct) {
                $this->processOrderProduct($order, $orderProduct, $order_data, $config);
            }

            $order_list[$order->id] = $order_data;
        }

        return $order_list;
    }

    /**
     * 初始化訂單基本資料
     */
    private function initializeOrderData($order)
    {
        $delivery_time_range = str_replace(' ', '', $order->delivery_time_range);
        $delivery_time_range_array = explode('-', $delivery_time_range);

        $delivery_time_range_start = substr($delivery_time_range_array[0], 0, 2) . ':' . substr($delivery_time_range_array[0], -2);
        $delivery_time_range_end = substr($delivery_time_range_array[1], 0, 2) . ':' . substr($delivery_time_range_array[1], -2);

        return [
            'order_id' => $order->id,
            'order_code' => substr($order->code, 4, 4),
            'required_datetime' => $order->delivery_date,
            'required_date_ymd' => Carbon::parse($order->delivery_date)->format('Y-m-d'),
            'delivery_time_range' => $delivery_time_range,
            'delivery_time_range_start' => $delivery_time_range_start,
            'delivery_time_range_end' => $delivery_time_range_end,
            'shipping_road_abbr' => $order->shipping_road_abbr,
            'order_url' => route('lang.admin.sale.orders.form', [$order->id]),
            'tooltip' => '',
            'items' => [],
        ];
    }

    /**
     * 處理訂單商品
     */
    private function processOrderProduct($order, $orderProduct, &$order_data, $config)
    {
        $product_id = $orderProduct->product_id;
        $printing_category_id = $orderProduct->product->printing_category_id;

        // 處理單點無選項商品
        if ($printing_category_id == self::PRINTING_CATEGORY_SINGLE_ITEM) {
            $this->processSingleItem($order, $orderProduct, $order_data);
        }

        // 處理商品選項
        foreach ($orderProduct->orderProductOptions ?? [] as $orderProductOption) {
            $this->processProductOption($order, $orderProduct, $orderProductOption, $order_data, $config);
        }

        // 記錄商品到 tooltip
        $order_data['tooltip'] .= '商品' . $orderProduct->sort_order . '：' . $orderProduct->name .
            ' ($' . (int)$orderProduct->price . ') * ' . $orderProduct->quantity . "<BR>";
    }

    /**
     * 處理單點無選項商品
     */
    private function processSingleItem($order, $orderProduct, &$order_data)
    {
        $product_id = $orderProduct->product_id;

        if (!isset($order_data['items'][$product_id])) {
            $order_data['items'][$product_id] = [
                'required_datetime' => $order->delivery_date,
                'delivery_time_range' => $order->delivery_time_range,
                'product_id' => $product_id,
                'product_name' => $orderProduct->name,
                'map_product_id' => $product_id,
                'map_product_name' => $orderProduct->name,
                'quantity' => 0,
            ];
        }

        $order_data['items'][$product_id]['quantity'] += $orderProduct->quantity;
    }

    /**
     * 處理商品選項
     */
    private function processProductOption($order, $orderProduct, $orderProductOption, &$order_data, $config)
    {
        $printing_category_id = $orderProduct->product->printing_category_id;
        $map_product_id = $orderProductOption->map_product_id ?? 0;
        $map_product_name = $orderProductOption->value ?? '';
        $quantity = $orderProductOption->quantity ?? 0;

        // 處理滷味特殊邏輯
        if (in_array($orderProductOption->option_value_id, $config['braisedfood_option_value_ids'])) {
            $this->processBraisedFood($order, $orderProduct, $orderProductOption, $order_data);
            return;
        }

        // 處理數量轉換
        $converted = $this->convertProductQuantity($map_product_id, $map_product_name, $quantity, $config);
        if ($converted) {
            $map_product_id = $converted['map_product_id'];
            $map_product_name = $converted['map_product_name'];
            $quantity = $converted['quantity'];
        }

        // 初始化項目
        if (!isset($order_data['items'][$map_product_id])) {
            $order_data['items'][$map_product_id] = [
                'required_datetime' => $order->delivery_date,
                'delivery_time_range' => $order->delivery_time_range,
                'product_id' => $orderProduct->product_id,
                'product_name' => $orderProduct->name,
                'map_product_id' => $map_product_id,
                'map_product_name' => $map_product_name,
                'quantity' => 0,
            ];
        }

        // 累加數量（排除單點無選項，因為已在上面處理）
        if ($printing_category_id != self::PRINTING_CATEGORY_SINGLE_ITEM) {
            $order_data['items'][$map_product_id]['quantity'] += $quantity;
        }
    }

    /**
     * 處理滷味商品（小、中、大）
     */
    private function processBraisedFood($order, $orderProduct, $orderProductOption, &$order_data)
    {
        $map_product_id = self::PRODUCT_BRAISED_FOOD_UNIT;
        $map_product_name = '滷味個';

        // 根據規格計算數量
        $quantity_multiplier = match($orderProductOption->option_value_id) {
            self::OPTION_VALUE_BRAISED_SMALL => 6,  // 一份滷味小= 6個
            self::OPTION_VALUE_BRAISED_MEDIUM => 9, // 一份滷味中= 9個
            self::OPTION_VALUE_BRAISED_LARGE => 12, // 一份滷味大=12個
            default => 1,
        };

        $quantity = $orderProductOption->quantity * $quantity_multiplier;

        // 初始化或累加
        if (!isset($order_data['items'][$map_product_id])) {
            $order_data['items'][$map_product_id] = [
                'required_datetime' => $order->delivery_date,
                'delivery_time_range' => $order->delivery_time_range,
                'product_id' => $orderProduct->product_id,
                'product_name' => $orderProduct->name,
                'map_product_id' => $map_product_id,
                'map_product_name' => $map_product_name,
                'quantity' => 0,
            ];
        }

        $order_data['items'][$map_product_id]['quantity'] += $quantity;
    }

    /**
     * 轉換商品數量（3吋→6吋、極品油飯→廚娘油飯等）
     *
     * @return array|null ['map_product_id', 'map_product_name', 'quantity'] 或 null
     */
    private function convertProductQuantity($map_product_id, $map_product_name, $quantity, $config)
    {
        // 3吋潤餅 → 6吋潤餅（除以2，向上取整）
        if (in_array($map_product_id, $config['wrap_ids_needing_halving'])) {
            $wrap_map = $config['sales_wrap_map'][$map_product_id];
            return [
                'map_product_id' => $wrap_map['new_product_id'],
                'map_product_name' => $wrap_map['new_product_name'],
                'quantity' => ceil($quantity / 2),
            ];
        }

        // 極品油飯 → 廚娘油飯（乘以2）
        if ($map_product_id == self::PRODUCT_EXCELLENT_OIL_RICE) {
            return [
                'map_product_id' => self::PRODUCT_CHEF_OIL_RICE,
                'map_product_name' => '廚娘油飯',
                'quantity' => $quantity * 2,
            ];
        }

        // 全素小刈包 → 鮮蔬小刈包
        if ($map_product_id == self::PRODUCT_ALL_VEGETARIAN_SMALL_GUABAO) {
            return [
                'map_product_id' => self::PRODUCT_VEGETABLE_SMALL_GUABAO,
                'map_product_name' => $map_product_name,
                'quantity' => $quantity,
            ];
        }

        // 奶素小刈包 → 鮮蔬小刈包
        if ($map_product_id == self::PRODUCT_DAIRY_VEGETARIAN_SMALL_GUABAO) {
            return [
                'map_product_id' => self::PRODUCT_VEGETABLE_SMALL_GUABAO,
                'map_product_name' => $map_product_name,
                'quantity' => $quantity,
            ];
        }

        return null;
    }

    // ==================== 私有方法 - 統計計算 ====================

    /**
     * 排序訂單列表
     */
    private function sortOrderList($order_list)
    {
        if (empty($order_list)) {
            return [];
        }

        return collect($order_list)
            ->sortBy('source_idsn')
            ->sortBy('delivery_time_range_end')
            ->values()
            ->all();
    }

    /**
     * 累加統計（全日、上午、下午）
     */
    private function accumulateStatistics($order_list, $config)
    {
        $statistics = [
            'allDay' => [], 'allDay_sgb' => 0, 'allDay_bgb' => 0, 'allDay_6in' => 0, 'allDay_sr' => 0,
            'am' => [], 'am_sgb' => 0, 'am_bgb' => 0, 'am_6in' => 0, 'am_sr' => 0,
            'pm' => [], 'pm_sgb' => 0, 'pm_bgb' => 0, 'pm_6in' => 0, 'pm_sr' => 0,
        ];

        foreach ($order_list as $order) {
            $is_morning = $order['delivery_time_range_start'] <= self::TIME_CUT_OFF;
            $time_range = $is_morning ? 'am' : 'pm';

            foreach ($order['items'] as $map_product_id => $item) {
                $quantity = $item['quantity'] ?? 0;

                // 累加全日統計
                $statistics['allDay'][$map_product_id] = ($statistics['allDay'][$map_product_id] ?? 0) + $quantity;
                $this->accumulateSpecialItems($statistics, 'allDay', $map_product_id, $quantity, $config);

                // 累加時段統計
                $statistics[$time_range][$map_product_id] = ($statistics[$time_range][$map_product_id] ?? 0) + $quantity;
                $this->accumulateSpecialItems($statistics, $time_range, $map_product_id, $quantity, $config);
            }
        }

        return $statistics;
    }

    /**
     * 累加特殊項目（小刈包、大刈包、6吋潤餅、春捲）
     */
    private function accumulateSpecialItems(&$statistics, $prefix, $map_product_id, $quantity, $config)
    {
        if (in_array($map_product_id, $config['small_guabao_ids'])) {
            $statistics[$prefix . '_sgb'] = ($statistics[$prefix . '_sgb'] ?? 0) + $quantity;
        } elseif (in_array($map_product_id, $config['big_guabao_ids'])) {
            $statistics[$prefix . '_bgb'] = ($statistics[$prefix . '_bgb'] ?? 0) + $quantity;
        } elseif (in_array($map_product_id, $config['lumpia6in_ids'])) {
            $statistics[$prefix . '_6in'] = ($statistics[$prefix . '_6in'] ?? 0) + $quantity;
        } elseif ($map_product_id == $config['spring_roll_id']) {
            $statistics[$prefix . '_sr'] = ($statistics[$prefix . '_sr'] ?? 0) + $quantity;
        }
    }

    /**
     * 計算總計（便當、盒餐、油飯盒）
     */
    private function calculateTotals($orders)
    {
        $total_bento = 0;
        $total_lunchbox = 0;
        $total_oil_rice_box = 0;

        $set_meal_printing_category_ids = [
            self::PRINTING_CATEGORY_LUMPIA_BENTO,
            self::PRINTING_CATEGORY_GUABAO_BENTO,
            self::PRINTING_CATEGORY_LUMPIA_LUNCHBOX,
            self::PRINTING_CATEGORY_GUABAO_LUNCHBOX,
            self::PRINTING_CATEGORY_OIL_RICE_BOX,
            self::PRINTING_CATEGORY_CUSTOM_BENTO,
            self::PRINTING_CATEGORY_CUSTOM_LUNCHBOX,
        ];

        foreach ($orders as $order) {
            foreach ($order->orderProducts as $orderProduct) {
                $printing_category_id = $orderProduct->product->printing_category_id;

                if (!in_array($printing_category_id, $set_meal_printing_category_ids)) {
                    continue;
                }

                // 便當
                if (in_array($printing_category_id, [
                    self::PRINTING_CATEGORY_LUMPIA_BENTO,
                    self::PRINTING_CATEGORY_GUABAO_BENTO,
                    self::PRINTING_CATEGORY_CUSTOM_BENTO,
                ])) {
                    $total_bento += $orderProduct->quantity;
                }

                // 盒餐
                if (in_array($printing_category_id, [
                    self::PRINTING_CATEGORY_LUMPIA_LUNCHBOX,
                    self::PRINTING_CATEGORY_GUABAO_LUNCHBOX,
                    self::PRINTING_CATEGORY_CUSTOM_LUNCHBOX,
                ])) {
                    $total_lunchbox += $orderProduct->quantity;
                }

                // 油飯盒
                if ($printing_category_id == self::PRINTING_CATEGORY_OIL_RICE_BOX) {
                    $total_oil_rice_box += $orderProduct->quantity;
                }
            }
        }

        return [
            'total_bento' => $total_bento,
            'total_lunchbox' => $total_lunchbox,
            'total_oil_rice_box' => $total_oil_rice_box,
            'total_set' => $total_bento + $total_lunchbox + $total_oil_rice_box,
        ];
    }
}
