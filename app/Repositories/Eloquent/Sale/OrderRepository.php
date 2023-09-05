<?php

namespace App\Repositories\Eloquent\Sale;

use App\Repositories\Eloquent\Repository;
use App\Repositories\Eloquent\Common\OptionRepository;
use App\Repositories\Eloquent\Common\OptionValueRepository;
use App\Models\Common\Option;

class OrderRepository extends Repository
{
    public $modelName = "\App\Models\Sale\Order";
    private $order_statuses;

    /**
     * 2023-09-06
     */
    public function getOrders($data=[], $debug=0)
    {
        $orders = $this->getRows($data, $debug);

        $statuses = $this->getOrderStatuses();

        foreach ($orders as $row) {
            $row->status_text = $statuses[$row->status_id]['name'];
        }

        return $orders;
    }


    /**
     * 2023-09-06
     */
    public function getOrderStatuses()
    {
        if(!empty($this->order_statuses)){
            return $this->order_statuses;
        }

        // $options = Option::select('option_values.id', 'option_values.option_id', 'option_value_translations.name')
        //     ->join('option_values', 'options.id', '=', 'option_values.option_id')
        //     ->join('option_value_translations', 'option_values.id', '=', 'option_value_translations.option_value_id')
        //     ->where('option_value_translations.locale', 'zh_Hant')
        //     ->where('options.code', 'order_status')
        //     ->where('option_values.is_active', 1)
        //     ->get();

        //Option
        $option = (new OptionRepository)->getRow(['filter_code'=>'order_status']);

        // Option Values
        $filter_data = [
            'equal_option_id' => $option->id,
            'equal_is_active' => '1',
            'sort' => 'sort_order',
            'order' => 'ASC',
            'pagination' => false,
            'limit' => 0,
        ];
        $option_values = (new OptionValueRepository)->getRows($filter_data)->toArray();

        $order_statuses = [];

        foreach ($option_values as $key => $row) {
            unset($row['translation']);
            $status_id = $row['id'];
            $order_statuses[$status_id] = $row;
        }

        return $order_statuses;
    }
}

