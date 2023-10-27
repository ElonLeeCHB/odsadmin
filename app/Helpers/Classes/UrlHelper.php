<?php

namespace App\Helpers\Classes;

class UrlHelper
{
    public static function getUrlQueriesForFilter()
    {
        $request = request();

        $queries = $request->query();

        $query_keys = array_keys($queries);

        $filter_data = [];

        
        // is_active
        //  - 將 filter_is_active 轉為 equal_is_active
        if(isset($queries['filter_is_active'])){
            $filter_data['equal_is_active'] = $queries['filter_is_active'];
            unset($queries['filter_is_active']);
        }

        // - 判斷 equal_is_active
        if(isset($queries['equal_is_active'])){
            $filter_data['equal_is_active'] = $queries['equal_is_active'];
        }else{
            $filter_data['equal_is_active'] = 1;
        }

        // filters
        foreach($queries as $key => $value){
            if(str_starts_with($key, 'filter_')){
                $filter_data[$key] = $value;
                unset($queries[$key]);
            }
            else if(str_starts_with($key, 'equal_')){
                $filter_data[$key] = $value;
                unset($queries[$key]);
            }
        }

        if(!empty($queries['sort'])){
            $filter_data['sort'] = $queries['sort'];
        }else{
            $filter_data['sort'] = 'id';
        }
        unset($queries['sort']);

        if(!empty($queries['order'])){
            $filter_data['order'] = $queries['order'];
        }else{
            $filter_data['order'] = 'DESC';
        }
        unset($queries['order']);

        if(isset($queries['page'])){
            $filter_data['page'] = $queries['page'];
        }
        unset($queries['page']);

        if(isset($queries['limit'])){
            $filter_data['limit'] = $queries['limit'];
        }
        unset($queries['limit']);

        /**
         * $with = array, separated by comma, and relations can be chained by dots
         * $with = [
         *     'order_products.products.product_options.option.option_values',
         *     'order_products.products.product_options.product_option_values'
         * ];
         */
        if(isset($queries['with'])){
            $filter_data['with'] = explode(',',$queries['with']);
            unset($queries['with']);
        }

        if(!empty($queries['extra_columns'])){
            $filter_data['extra_columns'] = explode(',',$queries['extra_columns']);
            unset($queries['extra_columns']);
        }

        // 0 or 1
        if(!empty($queries['simplelist'])){
            $filter_data['simplelist'] = $queries['simplelist'];
            unset($queries['simplelist']);
        }

        

        foreach ($queries as $key => $value) {
            $filter_data[$key] = $value;
        }
        
        return $filter_data;
    }

    public static function resetUrlQueries($unset_arr = [], $keep_arr = [])
    {
        $request = request();

        $queries = $request->query();

        foreach ($queries as $key => $value) {
            if(in_array($key, $unset_arr) && !in_array($key, $keep_arr)){
                unset($queries[$key]);
            }
        }

        return $queries;
    }
}