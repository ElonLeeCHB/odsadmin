<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Sale;

use App\Http\Controllers\Controller;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting\Setting;

class OrderMetadataController extends ApiPosController
{
    public function index()
    {
        // 用快取避免每次查 DB
        $metadata = Cache::remember('sale_order_metadata', 60 * 60, function () {

            $metadata = [];

            $setting_keys = ['sale_order_total_items'];
            // Setting::whereIn('setting_keys', $setting_keys)->get()->each(function ($setting) use (&$metadata) {
            //     $metadata[$setting->key] = json_decode($setting->value, true);
            // });
            $settings = Setting::whereIn('setting_key', $setting_keys)
                ->get()
                ->keyBy('setting_key');

            // 總計項目
            $sale_order_total_items = $settings['sale_order_total_items']->setting_value ?? null;

            $tmp_arr = [];

            foreach ($sale_order_total_items as $sale_order_total_item) {
                $code = $sale_order_total_item['code'];
                $locale = app()->getLocale();


                $tmp_arr[$code] = [
                    'label' => $sale_order_total_item['label'][$locale],
                    'type' => $sale_order_total_item['type'],
                    'sort_order' => $sale_order_total_item['sort_order'],
                ];
            }

            $sale_order_total_items = $tmp_arr;
            unset($tmp_arr);

            $metadata['sale_order_total_items'] = $sale_order_total_items;

            return $metadata;
        });

        return response()->json($metadata);
    }
}
