<?php

namespace App\Domains\Admin\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Repositories\Eloquent\Localization\LanguageRepository;

class ReceivingController extends BackendController
{
    public function __construct(
        private Request $request
        , private LanguageRepository $LanguageRepository
    )
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/inventory/receiving']);
    }


    public function index()
    {
        $data['lang'] = $this->lang;

        // Breadcomb
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_home,
            'href' => route('lang.admin.dashboard'),
        ];
        
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_product,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];
        
        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.catalog.categories.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        $data['list'] = $this->getList();

        $data['list_url']   =  route('lang.admin.catalog.categories.list');
        $data['add_url']    = route('lang.admin.catalog.categories.form');
        $data['delete_url'] = route('lang.admin.catalog.categories.delete');

        return view('admin.catalog.category', $data);
    }


    public function list()
    {
        return $this->getList();
    }


    private function getList()
    {
        $data['lang'] = $this->lang;


        // Prepare query_data for records
        $url_query_data = $this->getQueries($this->request->query());

        // Extra
        $url_query_data['equal_taxonomy_code'] = 'product_category';
        $url_query_data['equal_is_active'] = 1;

        // Rows
        $categories = $this->CategoryService->getRows($url_query_data);

        if(!empty($categories)){
            foreach ($categories as $row) {
                $row->edit_url = route('lang.admin.catalog.categories.form', array_merge([$row->id], $url_query_data));
            }
        }

        $data['categories'] = $categories->withPath(route('lang.admin.catalog.categories.list'))->appends($url_query_data);

        
        // Prepare links for list table's header
        if($url_query_data['order'] == 'ASC'){
            $order = 'DESC';
        }else{
            $order = 'ASC';
        }
        
        $data['sort'] = strtolower($url_query_data['sort']);
        $data['order'] = strtolower($order);

        $url_query_data = $this->unsetUrlQueryData($url_query_data);

        $url = '';

        foreach($url_query_data as $key => $value){
            $url .= "&$key=$value";
        }
        
        
        // link of table header for sorting        
        $route = route('lang.admin.catalog.categories.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_code'] = $route . "?sort=code&order=$order" .$url;
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_short_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_taxonomy_name'] = $route . "?sort=taxonomy_name&order=$order" .$url;
        $data['sort_is_active'] = $route . "?sort=is_active&order=$order" .$url;
        $data['sort_date_added'] = $route . "?sort=created_at&order=$order" .$url;

        $data['list_url'] = route('lang.admin.catalog.categories.list');

        return view('admin.catalog.category_list', $data);
    }






}