<?php

namespace App\Domains\Admin\Http\Controllers\Catalog;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Domains\Admin\Services\Catalog\PosCategoryService;

class PosCategoryController extends BackendController
{
    public function __construct(private PosCategoryService $PosCategoryService)
    {
        parent::__construct();
    }
    
    public function autocomplete()
    {

        // Rows
        $rows = $this->PosCategoryService->getAutocomplete($this->url_data);

        $json = [];

        // foreach ($rows as $row) {
        //     if(!empty($query_data['exclude_id']) && $query_data['exclude_id'] == $row->id){
        //         continue;
        //     }

        //     $json[] = array(
        //         'label' => $row->name,
        //         'value' => $row->id,
        //         'category_id' => $row->id,
        //         'code' => $row->code,
        //         'name' => $row->name,
        //         'short_name' => $row->short_name,
        //         'parent_name' => $row->parent_name ?? '',
        //     );
        // }

        // return response(json_encode($json))->header('Content-Type','application/json');
        return response(json_encode($rows))->header('Content-Type','application/json');
    }
}