<?php

namespace App\Domains\Api\Http\Controllers\Sale;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Domains\Api\Http\Controllers\ApiController;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Domains\Api\Services\Sale\OrderService;
use App\Domains\Api\Services\Member\MemberService;
//use App\Domains\Api\Services\User\UserService;
use App\Repositories\Eloquent\User\UserRepository;
use App\Domains\Api\Services\Catalog\ProductService;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Domains\Api\Services\Localization\CountryService;
use App\Domains\Api\Services\Localization\DivisionService;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderController extends ApiController
{
    public function __construct(
        private Request $request,
        private OrderService $OrderService,
        private MemberService $MemberService,
        //private UserService $UserService,
        private UserRepository $UserRepository,
        private TermRepository $TermRepository,
        private ProductService $ProductService,
        private CountryService $CountryService,
        private DivisionService $DivisionService,
        )
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/sale/order']);
    }

    /**
     * Show the list table.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function list()
    {
        $query_data = $this->request->query();

        $filter_data = $this->getQueries($query_data);

        $orders = $this->OrderService->getOrders($filter_data);

        $orders = $this->OrderService->optimizeRows($orders);

        $this->OrderService->unsetRelations($orders, ['status']);

        return response(json_encode($orders))->header('Content-Type','application/json');
    }


    // 包含訂單的單頭、單身
    public function details($order_id)
    {
        $order = $this->OrderService->findIdOrFailOrNew($order_id);
        $order->load('order_products.order_product_options');

        $order->status_name = $order->status->name ?? '';

        $order = $this->OrderService->sanitizeRow($order);

        // Order Total
        $order->totals = $this->OrderService->getOrderTotals($order_id);

        return response(json_encode($order))->header('Content-Type','application/json');
    }


    public function save()
    {
        $post_data = $this->request->post();

        if(isset($postData['customer_id'])){
            $customer_id = $postData['customer_id'];
        }else if(isset($postData['member_id'])){
            $customer_id = $postData['member_id'];
        }else{
            $customer_id = null;
        }

        if(!empty($this->request->query('getReturn'))){
            return response(json_encode($post_data))->header('Content-Type','application/json');
        }

        $json = [];

        if(empty($this->request->mobile) && empty($this->request->telephone)){
            $json['error']['mobile'] = $this->lang->error_phone;
            $json['error']['telephone'] = $this->lang->error_phone;
        }

        if(empty($this->request->location_id)){
            $json['error']['location_id'] = '請指定門市代號';
        }

        //檢查姓名+手機不可重複
        if(!empty($customer_id) && !empty($this->request->mobile) && !empty($this->request->personal_name)){
            
            $filter_data = [
                'equal_name' => $this->request->personal_name,
                'equal_mobile' => preg_replace('/\D+/', '', $this->request->mobile),
                'pagination' => false,
                'select' => ['id', 'name', 'mobile'],
            ];
            $member = $this->UserRepository->getRow($filter_data);

            if($member && $member->id != $customer_id){
                $json['error']['personal_name'] = '此姓名+手機的客戶資料已存在！';
                $json['error']['mobile'] = '此姓名+手機的客戶資料已存在！';
            }
        }

        // Validate
        //驗證表單內容
        // $validator = $this->OrderService->validator($post_data);

        // if($validator->fails()){
        //     $messages = $validator->errors()->toArray();
        //     foreach ($messages as $key => $rows) {
        //         $json['error'][$key] = $rows[0];
        //     }
        // }

        //表單驗證成功
        if (!$json) {
            $result = $this->OrderService->updateOrCreate($post_data);

            if(empty($result['error'])){

                $order = $result['data'];
                
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
                    $json['error'] = $this->lang->text_fail;
                    $json['error'] = $result['error'];
                }
                
            }
        }


        return response(json_encode($json))->header('Content-Type','application/json');
    }


    public function getActiveOrderStatuses()
    {
        $allStatuses = $this->OrderService->getCachedActiveOrderStatuses();

        return response(json_encode($allStatuses))->header('Content-Type','application/json');
    }


    public function getOrderPhrasesByTaxonomyCode($taxonomy_code)
    {
        $query_data = $this->request->query();

        $query_data['equal_taxonomy_code'] = $taxonomy_code;

        $rows = $this->TermRepository->getTerms($query_data)->toArray();

        $rows['data'] = $this->TermRepository->unsetArrayRelations($rows['data'], ['translation', 'taxonomy']);
        
        return response(json_encode($rows))->header('Content-Type','application/json');
    }
    
}
