<?php

namespace App\Domains\ApiV2\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Domains\ApiV2\Http\Controllers\ApiV2Controller;
use App\Domains\ApiV2\Services\Sale\OrderService;

class OrderController extends ApiV2Controller
{
    public function __construct(private Request $request,private OrderService $OrderService)
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }
    }


    public function list()
    {
        if(!empty($this->url_data['simplelist'])){
            $orders = $this->OrderService->getSimplelist($this->url_data);
        }else{
            $orders = $this->OrderService->getList($this->url_data);
        }

        return response(json_encode($orders))->header('Content-Type','application/json');
    }


    public function info($order_id)
    {
        $order = $this->OrderService->getInfo($order_id, 'id');

        return response(json_encode($order))->header('Content-Type','application/json');
    }


    public function infoByCode($code)
    {
        $order = $this->OrderService->getInfo($code, 'code');

        return response(json_encode($order))->header('Content-Type','application/json');
    }


    public function save()
    {
        $post_data = $this->request->post();
        $post_data['source'] = isset($post_data['source']) ? $post_data['source'] : null;//來源
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
            //新會員才更新會員資料
                $result = $this->OrderService->updateOrCreate($post_data);
            if(empty($result['error'])){
                $order = $result['data'];
                //接單人員
                if(isset($post_data['order_taker'])){
                    $this->insertOrderTakerForAdd($post_data['order_taker'],$order->id);
                }
                $redirectUrl = route('api.sale.order.details', $order->id);

                $json = [
                    'success' => $this->lang->text_success,
                    'order_id' => $order->id,
                    'code' => $order->code,
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

}
