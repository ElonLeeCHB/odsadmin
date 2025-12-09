<?php

namespace App\Domains\Admin\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Libraries\TranslationLibrary;
use App\Domains\Admin\Services\Sale\OrderService;
use App\Domains\Admin\Services\Member\MemberService;
use App\Repositories\Eloquent\User\UserRepository;
use App\Domains\Admin\Services\Catalog\ProductService;
use App\Domains\Admin\Services\Catalog\OptionService;
use App\Domains\Admin\Services\Localization\CountryService;
use App\Domains\Admin\Services\Localization\DivisionService;
use Illuminate\Support\Facades\DB;
//use Maatwebsite\Excel\Facades\Excel;
use App\Domains\Admin\ExportsLaravelExcel\OrderProductExport;
use App\Domains\Admin\ExportsLaravelExcel\UsersExport;
use Carbon\Carbon;
use App\Helpers\Classes\DataHelper;
use App\Http\Resources\Sale\OrderProductResource;
use Elibyy\TCPDF\Facades\TCPDF;
use Illuminate\Support\Facades\File;
use App\Repositories\Eloquent\Sale\OrderPrintingRepository;

class OrderPringintController extends BackendController
{
    private $order;

    public function __construct(private OrderService $OrderService,)
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }

        $this->getLang(['admin/common/common', 'admin/sale/order']);
    }

    public function printMultiOrders()
    {
        $request_data = request()->all();
        $url_data = request()->query();

        $cache_key = 'cache/orders/PrintingData_' . app()->getLocale() . '_' . md5(json_encode($url_data));

        if (!empty($url_data['fresh'])) {
            DataHelper::deleteDataFromStorage($cache_key);
        }

        DataHelper::deleteDataFromStorage($cache_key);

        //
        // $result = $this->OrderService->getMultiOrdersForPrinting($request_data);
        $result = (new OrderPrintingRepository)->getMultiOrders($request_data);

        $data = [];

        if (empty($result['error'])) {
            $data['orders'] = $result;

            //固定欄位的選項

            //油飯盒

            //潤餅便當 lumpiaBento 以 1001 招牌潤餅便當 當代表
            $data['columns']['lumpiaBento']['MainMeal'] = $this->OrderService->getOptionValuesByProductOption(product_id: 1001, option_id: 1003);

            //刈包便當 guabaoBento 以 1671 雞胸刈包便當 當代表
            $data['columns']['guabaoBento']['MainMeal'] = $this->OrderService->getOptionValuesByProductOption(product_id: 1671, option_id: 1003);

            //潤餅盒餐 lumpiaLunchBox 以 1008 潤餅首席盒餐 當代表
            $data['columns']['lumpiaLunchBox']['MainMeal'] = $this->OrderService->getOptionValuesByProductOption(product_id: 1008, option_id: 1003);
            $data['columns']['lumpiaLunchBox']['SideDish'] = $this->OrderService->getOptionValuesByProductOption(product_id: 1008, option_id: 1005);

            //刈包盒餐 guabaoLunchBox 以 1680 刈包首席盒餐 當代表
            $data['columns']['guabaoLunchBox']['MainMeal'] = $this->OrderService->getOptionValuesByProductOption(product_id: 1680, option_id: 1003);
            $data['columns']['guabaoLunchBox']['SideDish'] = $this->OrderService->getOptionValuesByProductOption(product_id: 1680, option_id: 1005);

            //分享餐的主餐 使用潤餅便當
            $data['columns']['otherFlavors']['MainMeal'] = $data['columns']['lumpiaBento']['MainMeal'];

            //飲料 抓選項
            $data['columns']['Drink'] = $this->OrderService->getDrinks();

            //單點豆花
            // $data['columns']['Douhua'] = $this->OrderService->getDouhua();
            $data['columns']['Douhua'] = [
                (object) ['short_name' => '紅豆', 'option_value_ids' => [1127, 1214]],
                (object) ['short_name' => '綠豆', 'option_value_ids' => [1128, 1215]],
                (object) ['short_name' => '花生', 'option_value_ids' => [1131, 1216]],
            ];

            $data['columns']['MainMeal'] = [
                (object) ['short_name' => '主廚', 'option_value_ids' => [1083, 1102, 1095]],
                (object) ['short_name' => '奶素', 'option_value_ids' => [1047, 1105, 1058]],
                (object) ['short_name' => '鮮蔬', 'option_value_ids' => [1017, 1096, 1059]],
                (object) ['short_name' => '炸蝦', 'option_value_ids' => [1018, 1097, 1060]],
                (object) ['short_name' => '芥雞', 'option_value_ids' => [1019, 1098, 1061]],
                (object) ['short_name' => '酥魚', 'option_value_ids' => [1020, 1099, 1062]],
                (object) ['short_name' => '培根', 'option_value_ids' => [1021, 1100, 1063]],
                (object) ['short_name' => '滷肉', 'option_value_ids' => [1022, 1101, 1064]],
                (object) ['short_name' => '春捲', 'option_value_ids' => [1093]],
                (object) ['short_name' => '米食', 'option_value_ids' => [1211]],
            ];
            //

            if (empty($request_data['template'])) {
                $request_data['template'] = 'V03';
            }

            if ($request_data['template'] == 'V03') {
                foreach ($data['orders'] ?? [] as $key1 => &$order) {
                    foreach ($order['categories'] ?? [] as $category_code => &$category) {
                        foreach ($category['items'] ?? [] as $product_id => &$product) {
                            // term_id: 1439 客製, 13229 便當
                            if (!empty($product['product_tag_ids']) && !in_array(1439, $product['product_tag_ids']) && in_array(13229, $product['product_tag_ids'])) {
                                unset($data['orders'][$key1]['categories'][$category_code]['items'][$product_id]['product_options']['SideDish']);
                            }
                        }
                    }
                }
            }

            $data['template'] = $request_data['template'];
        }

        


        if (empty($data['error'])) {
            return view('admin.sale.printMultiOrders', $data);
        }

        return response()->json(['error' => $data['error']], 500);
    }
}


