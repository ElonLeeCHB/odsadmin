<?php

/**
 * 這個檔不要再修改，以後改用 api 裡面的 service
 */

namespace App\Services\Sale;

use App\Libraries\TranslationLibrary;
use App\Domains\Admin\Services\Service;
use App\Domains\Admin\Services\Common\OptionService;
use App\Helpers\Classes\TwAddress;

use App\Repositories\Eloquent\Common\TermRepository;
use App\Models\Common\Term;
use App\Models\Common\TermTranslation;
use App\Models\Common\TermRelation;

use App\Repositories\Eloquent\Sale\OrderRepository;
use App\Repositories\Eloquent\Sale\OrderProductRepository;
use App\Repositories\Eloquent\Sale\OrderProductOptionRepository;
use App\Repositories\Eloquent\Sale\OrderTotalRepository;
use App\Repositories\Eloquent\Member\MemberRepository;
use App\Models\Catalog\ProductTranslation;
use App\Models\Localization\Division;
use Illuminate\Support\Facades\Validator;
use DB;

class OrderService extends Service
{
    private $lang;

	public function __construct(protected OrderRepository $repository
        , private OrderProductRepository $OrderProductRepository
        , private OrderProductOptionRepository $OrderProductOptionRepository
        , private OrderTotalRepository $OrderTotalRepository
        , private OptionService $OptionService
        , private MemberRepository $MemberRepository
        , private TermRepository $TermRepository
    )
	{}


    public function getOrders($data=[], $debug=0)
    {
        //if(!empty($data['filter_predifined'])){
            // if($data['filter_predifined'] == 'tomorrow'){
            //     $delivery_date = Carbon::now()->addDay()->format('Y-m-d');
            // }
        //}

        //送達日 $delivery_date
        if(!empty($data['filter_delivery_date'])){
            $rawSql = $this->parseDateToSqlWhere('delivery_date', $data['filter_delivery_date']);
            if($rawSql){
                $data['WhereRawSqls'][] = $rawSql;
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
            $data['filter_shipping_state_id'] = '=' . $data['filter_shipping_state_id'];
        }

        if(!empty($data['filter_shipping_city_id'])){
            $data['filter_shipping_city_id'] = '=' . $data['filter_shipping_city_id'];
        }

        $records = $this->repository->getRows($data, $debug);

        return $records;
    }

    public function getOrderStatuses()
    {
        //Option
        $option = $this->OptionService->getRow(['filter_code'=>'order_status']);

        // Option Values
        $filter_data = [
            'filter_option_id' => $option->id,
            'filter_is_active' => '1',
            'sort' => 'sort_order',
            'order' => 'ASC',
            'regexp' => false,
            'pagination' => false,
            'limit' => 0,
        ];
        $option_values = $this->OptionService->getValues($filter_data)->toArray();

        foreach($option_values as $key => $option_value){
            unset($option_value['translation']);
            $option_value_id = $option_value['id'];
            $result[$option_value_id] = $option_value;
        }

        return $result;
    }

    public function getSalutations()
    {
        $this->OrderService->getOrderPhrases('phrase_order_comment');
    }
}

