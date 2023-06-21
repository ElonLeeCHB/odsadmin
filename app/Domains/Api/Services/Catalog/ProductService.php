<?php

namespace App\Domains\Api\Services\Catalog;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Libraries\TranslationLibrary;
use App\Traits\Model\EloquentTrait;
use App\Domains\Api\Services\Service;
use App\Domains\Api\Services\Catalog\CategoryService;
use App\Domains\Api\Services\Catalog\ProductOptionService;

class ProductService extends Service
{
    use EloquentTrait;

    public $modelName;
    public $model;
    public $table;
    public $lang;

	public function __construct(public CategoryService $CategoryService
        , private ProductOptionService $ProductOptionService
        )
	{
        $this->modelName = "\App\Models\Catalog\Product";
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/member/member',]);
	}

    public function getProducts($data=[], $debug = 0)
    {
        if(!empty($data['filter_keyword'])){
            $arr['filter_name'] = $data['filter_keyword'];
            $arr['filter_short_name'] = $data['filter_keyword'];
            $arr['filter_description'] = $data['filter_keyword'];
            unset($data['filter_keyword']);
        }

        if(!empty($arr)){
            $data['whereHas']['translation'] = $arr;
        }
        $rows = $this->getRecords($data);

        return $rows;
    }

    public function getProduct($data = [])
    {
        $cacheName = app()->getLocale() . 'ProductId_' . $data['filter_id'];

        $result = cache()->remember($cacheName, 60*60*24*14, function() use($data){
            $collection = $this->getRecord($data);

            return $collection;
        });

        if(empty($result)){
            $result = [];
        }

        return $result;
    }


    public function getTotalProductsByOptionId($option_id)
    {
        $filter_data = [
            'filter_option_id' => $option_id,
            'regexp' => false,
        ];

        return $this->ProductOptionService->getCount($filter_data);
    }


    public function getSalableProducts($filter_data = [])
    {
        $cacheName = app()->getLocale() . '_salable_products';

        $result = cache()->remember($cacheName, 60*60*24*14, function() use($filter_data){
            if(empty($filter_data)){
                $filter_data = [
                    'filter_is_active' => 1,
                    'filter_is_salable' => 1,
                    'regexp' => false,
                    'limit' => 0,
                    'pagination' => false,
                    'sort' => 'sort_order',
                    'order' => 'ASC',
                    'with' => ['main_category','translation'],
                ];
            }
            $collections = $this->getRows($filter_data);

            return $collections;
        });

        if(empty($result)){
            $result = [];
        }

        return $result;
    }


    public function validator(array $data)
    {
        return Validator::make($data, [
                'organization_id' =>'nullable|integer',
                'name' =>'nullable|max:10',
                'short_name' =>'nullable|max:10',
            ],[
                'organization_id.integer' => $this->lang->error_organization_id,
                'name.*' => $this->lang->error_name,
                'short_name.*' => $this->lang->error_short_name,
        ]);
    }

}
