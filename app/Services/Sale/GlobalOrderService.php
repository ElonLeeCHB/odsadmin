<?php

namespace App\Services\Sale;

use App\Domains\Admin\Services\Service;
use App\Repositories\Eloquent\Sale\OrderRepository;
use App\Repositories\Eloquent\Sale\OrderTotalRepository;
use App\Models\Common\Term;

class GlobalOrderService extends Service
{
    protected $modelName = "\App\Models\Sale\Order";

    public function __construct(protected OrderRepository $OrderRepository, protected OrderTotalRepository $OrderTotalRepository)
    {}


    public function getOrder($data = [], $debug = 0)
    {
        $order = $this->OrderRepository->getOrder($data, $debug);

        if($data['sanitize']){
            $order = $this->sanitizeRow($order);
        }

        return $this->OrderRepository->getOrder($data, $debug);
    }


    public function getOrders($data = [], $debug = 0)
    {
        $orders = $this->OrderRepository->getOrders($data, $debug);

        if(!empty($data['sanitize'])){
            $orders = $this->sanitizeRows($orders);
        }

        return $orders;
    }


    public function optimizeRow($row)
    {
        return $this->OrderRepository->optimizeRow($row);
    }


    public function sanitizeRow($row)
    {
        return $this->OrderRepository->sanitizeRow($row);
    }


    public function getOrderTotals($order_id, $debug = 0)
    {
        $filter_data = [
            'equal_order_id' => $order_id,
            'sort' => 'sort_order',
            'order' => 'ASC',
            'limit' => 0,
            'pagination' => false,
        ];

        $totals = $this->OrderTotalRepository->getRows($filter_data, $debug);

        return $this->rowsToStdObj($totals);
    }


    public function getCachedActiveOrderStatuses($reset = false)
    {
        return $this->OrderRepository->getCachedActiveOrderStatuses($reset);
    }


    public function getOrderPhrases($taxonomy_code)
    {
        $result = Term::where('taxonomy_code', $taxonomy_code)->with('translation')->orderBy('sort_order', 'asc')->get();
        return $result;
    }

    
    // tag

    public function getOrderTags($qStr)
    {
        $tags = Term::where('taxonomy_code', 'order_tag')->whereHas('translation', function ($query) use ($qStr) {
            $query->where('name', 'like', '%'.$qStr.'%');
        })->with('translation')->get();

        return $tags;
    }

    public function getAllActiveOrderTags()
    {
        $tags = Term::where('taxonomy_code', 'order_tag')->where('is_active',1)->with('translation')->get();
        return $tags;
    }

    public function getOrderTagsByOrderId($order_id)
    {
        $tags = Term::where('taxonomy_code', 'order_tag')->whereHas('term_relations', function ($query) use ($order_id) {
            $query->where('object_id', $order_id);
        })->get();

        if(count($tags)==0){
            return [];
        }

        // $result = '';
        $result = [];

        foreach ($tags as $key => $tag) {
            //$result .= $tag->translation->name. ',';
            $result[] = $tag->translation->name;
        }

        return $result;
    }
}
