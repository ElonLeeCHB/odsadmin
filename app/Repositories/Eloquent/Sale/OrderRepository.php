<?php

namespace App\Repositories\Eloquent\Sale;

use App\Repositories\Eloquent\Repository;
use App\Repositories\Eloquent\Common\OptionRepository;
use App\Repositories\Eloquent\Common\OptionValueRepository;
use App\Models\Sale\Order;
use App\Models\Common\Term;
use App\Models\Common\Option;
use App\Models\Common\OptionValue;

class OrderRepository extends Repository
{
    public $modelName = "\App\Models\Sale\Order";
    private $order_statuses;

    public function __construct(private OptionValueRepository $OptionValueRepository)
    {
        parent::__construct();
    }


    public function getOrder($data=[], $debug=0)
    {
        $data = $this->resetQueryData($data);

        $order = $this->getRow($data, $debug);
        
        $order = $this->optimizeRow($order);

        return $order;
    }


    public function getOrders($data=[], $debug=0)
    {
        $data = $this->resetQueryData($data);

        $orders = $this->getRows($data, $debug);

        foreach ($orders as $order) {
            $order = $this->optimizeRow($order);
        }

        return $orders;
    }


    public function optimizeRow($row)
    {
        if(!empty($row->status_id)){
            $row->status_name = $row->status->name;
        }

        return $row;
    }

    public function sanitizeRow($row)
    {
        $arrOrder = $row->toArray();

        if(!empty($arrOrder['status'])){
            unset($arrOrder['status']);
        }

        if(!empty($arrOrder['totals'])){
            $arr = [];
            foreach ($arrOrder['totals'] as $key => $total) {
                $arr[$key] = (object) $total->toArray();
                $arrOrder['totals'] = $arr;
            }
        }

        return (object) $arrOrder;
    }


    public function sanitizeRows($rows)
    {
        foreach ($rows as $key => $row) {
            $rows[$key] = $this->sanitizeRow($row);
        }

        return $rows;
    }

    public function resetQueryData($data)
    {
        //送達日 $delivery_date
        if(!empty($data['filter_delivery_date'])){
            $rawSql = $this->parseDateToSqlWhere('delivery_date', $data['filter_delivery_date']);
            if($rawSql){
                $data['whereRawSqls'][] = $rawSql;
            }
            unset($data['filter_delivery_date']);
        }
        //

        if(!empty($data['filter_phone'])){
            $data['filter_phone'] = str_replace('-','',$data['filter_phone']);
            $data['filter_phone'] = str_replace(' ','',$data['filter_phone']);

            $data['andOrWhere'][] = [
                'filter_mobile' => $data['filter_phone'],
                'filter_telephone' => $data['filter_phone'],
            ];
            unset($data['filter_phone']);
        }

        if(!empty($data['filter_keyname'])){
            $data['andOrWhere'][] = [
                'filter_personal_name' => $data['filter_keyname'],
                'filter_shipping_personal_name' => $data['filter_keyname'],
                'filter_shipping_company' => $data['filter_keyname'],
                'filter_payment_company' => $data['filter_keyname'],
            ];
            unset($data['filter_keyname']);
        }

        if(!empty($data['filter_shipping_state_id'])){
            $data['equal_shipping_state_id'] = $data['filter_shipping_state_id'];
        }

        if(!empty($data['filter_shipping_city_id'])){
            $data['equal_shipping_city_id'] = $data['filter_shipping_city_id'];
        }

        return $data;
    }

    public function getOrderStatuses($data = [])
    {
        //Option
        $option = Option::where('code', 'order_status')->first();

        // Option Values
        $filter_data = [
            'filter_option_id' => $option->id,
            'equal_is_active' => $data['equal_is_active'] ?? '*',
            'sort' => 'sort_order',
            'order' => 'ASC',
            'regexp' => false,
            'pagination' => false,
            'limit' => 0,
        ];
        $option_values = $this->OptionValueRepository->getRows($filter_data)->toArray();

        $result = [];

        foreach($option_values as $key => $option_value){
            unset($option_value['translation']);
            $option_value_id = $option_value['id'];
            $result[$option_value_id] = (object) $option_value;
        }
    }

    public function getCachedActiveOrderStatuses($reset = false)
    {
        $cachedStatusesName = app()->getLocale() . '_order_statuses';

        // 取得快取
        if(empty($data['reset'])){
            $order_statuses = cache()->get($cachedStatusesName);

            if(!empty($order_statuses)){
                return $order_statuses;
            }
        }

        // 重設
        $filter_data = [
            'equal_is_active' => true,
        ];

        $order_statuses = $this->getOrderStatuses($filter_data);

        cache()->forever($cachedStatusesName, $order_statuses);

        return $order_statuses;
    }
    
}

