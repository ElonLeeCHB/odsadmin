<?php

namespace App\Domains\Api\Http\Controllers\Sale;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Libraries\TranslationLibrary;
use App\Domains\Api\Services\Sale\OrderService;
use App\Domains\Api\Services\Member\MemberService;
use App\Domains\Api\Services\User\UserService;
use App\Domains\Api\Services\Catalog\ProductService;
use App\Domains\Api\Services\Common\OptionService;
use App\Domains\Api\Services\Localization\CountryService;
use App\Domains\Api\Services\Localization\DivisionService;

class OrderController extends Controller
{
    private $lang;
    private $salable_products;
    private $order;
    private $sorted_order_products; //array and key is order_product_id

    public function __construct(
        private Request $request,
        private OrderService $OrderService,
        private MemberService $MemberService,
        private UserService $UserService,
        private ProductService $ProductService,
        private OptionService $OptionService,
        private CountryService $CountryService,
        private DivisionService $DivisionService,
        )
    {
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/common/common','admin/sale/order',]);
    }

    /**
     * Show the list table.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function list()
    {
        $data['lang'] = $this->lang;

        // Prepare link for action
        $queries = [];

        if(!empty($this->request->query('page'))){
            $page = $queries['page'] = $this->request->input('page');
        }else{
            $page = $queries['page'] = 1;
        }

        if(!empty($this->request->query('sort'))){
            $sort = $queries['sort'] = $this->request->input('sort');
        }else{
            $sort = $queries['sort'] = 'id';
        }

        if(!empty($this->request->query('order'))){
            $order = $queries['order'] = $this->request->query('order');
        }else{
            $order = $queries['order'] = 'asc';
        }

        if(empty($this->request->query('pagination'))){
           $queries['pagination'] = true;
        }else{
            //$queries['pagination'] = $this->request->query('pagination');
            $queries['pagination'] = false;
        }

        if(!empty($this->request->query('limit'))){
            $limit = $queries['limit'] = $this->request->query('limit');
        }

        foreach($this->request->all() as $key => $value){
            if(strpos($key, 'filter_') !== false){
                $queries[$key] = $value;
            }
        }

        $orders = $this->OrderService->getOrders($queries);

        $arr_all_statuses = $this->OrderService->getOrderStatuses();
        
        $arr_all_salutations = $this->OrderService->getOrderStatuses();

        if(!empty($orders)){
            foreach ($orders as $record) {
                $record->edit_url = route('api.sale.order.details', array_merge([$record->id], $queries));
                $record->payment_phone = $record->payment_mobile . "<BR>" . $record->payment_telephone;
				$record->status_txt = $arr_all_statuses[$record->status_id]['name'] ?? '';
            }
        }

        return response(json_encode($orders))->header('Content-Type','application/json');
    }


    public function details($order_id)
    {
        $order = $this->OrderService->find($order_id);

        //$order->load('order_products');

        $arr_all_statuses = $this->OrderService->getOrderStatuses();

        $order->status_txt = $arr_all_statuses[$order->status_id]['name'] ?? '';

        return response(json_encode($order))->header('Content-Type','application/json');
    }


    public function save()
    {
        $data = $this->request->all();

        if(!empty($this->request->query('getReturn'))){
            return response(json_encode($data))->header('Content-Type','application/json');
        }

        $json = [];

        if(empty($this->request->mobile) && empty($this->request->telephone)){
            $json['error']['mobile'] = $this->lang->error_phone;
            $json['error']['telephone'] = $this->lang->error_phone;
        }

        if(empty($this->request->location_id)){
            $json['error']['location_id'] = '請指定門市代號';
        }

        // Validate
        //驗證表單內容
        $validator = $this->OrderService->validator($data);

        if($validator->fails()){
            $messages = $validator->errors()->toArray();
            foreach ($messages as $key => $rows) {
                $json['error'][$key] = $rows[0];
            }
        }

        //表單驗證成功
        if (!$json) {
            $result = $this->OrderService->updateOrCreate($data);

            if(empty($result['error'])){

                $order = $result['order'];
                
                $redirectUrl = route('api.sale.order.details', $order->id);

                $json = [
                    'success' => $this->lang->text_success,
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'personal_name' => $order->personal_name,
                    'customer' => $order->customer_id . '_' . $order->personal_name,
                    'email' => $order->email,
                    'mobile' => $order->mobile,
                    'telephone' => $order->telephone,
                    'redirectUrl' => $redirectUrl,
                ];
            }else{
                //$user_id = auth()->user()->id ?? null;
                //if($user_id == 1){
                if(config('app.debug')){
                    $json['error'] = 'Debug: '.$result['error'];

                }else{
                    //$json['error'] = $this->lang->text_fail;
                    $json['error'] = $result['error'];
                }
                
            }
        }


        return response(json_encode($json))->header('Content-Type','application/json');
    }


    public function getAllStatuses()
    {
        $allStatuses = $this->OrderService->getOrderStatuses();

        return response(json_encode($allStatuses))->header('Content-Type','application/json');
    }


    public function getOrderPhrases($taxonomy_code)
    {
        $json = $this->OrderService->getOrderPhrases($taxonomy_code)->toArray();

        return response(json_encode($json))->header('Content-Type','application/json');
    }

}
