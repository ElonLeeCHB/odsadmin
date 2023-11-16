<?php

namespace App\Domains\Admin\Http\Controllers\Sale;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Repositories\Eloquent\Sale\OrderRepository;
use App\Repositories\Eloquent\Setting\SettingRepository;
use App\Models\Sale\OrderProductIngredient;
use App\Models\Sale\OrderProductIngredientDaily;
use App\Models\Setting\Setting;
use App\Domains\Admin\Services\Sale\OrderIngredientDailyService;

class OrderIngredientDailyController extends BackendController
{
    private $required_date;
    private $required_date_2ymd;

    public function __construct(
        private Request $request,
        private OrderIngredientDailyService $OrderIngredientDailyService,
        private OrderRepository $OrderRepository,
        private SettingRepository $SettingRepository,
        )
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/sale/order_ingredient']);
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
            'text' => $this->lang->text_menu_sale,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.sale.orderingredientsdailies.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        $data['list'] = $this->getList();

        $data['list_url'] = route('lang.admin.sale.orderingredientsdailies.list');
        
        $data['export_counting_product_list'] = route('lang.admin.inventory.countings.export_counting_product_list');


        return view('admin.sale.order_ingredient_daily', $data);
    }

    public function list()
    {
        return $this->getList();
    }

    private function getList()
    {
        $data['lang'] = $this->lang;
    
    
        // Prepare query_data for records
        $query_data = $this->getQueries($this->request->query());
    
        // Rows
        $ingredients = $this->OrderIngredientDailyService->getDailyIngredients($query_data);
    
        foreach ($ingredients as $row) {
            $row->edit_url = route('lang.admin.sale.orderingredientsdailies.form', array_merge([$row->id], $query_data));
            $row->is_active_name = ($row->is_active==1) ? $this->lang->text_enabled :$this->lang->text_disabled;
            $row->product_edit_url = route('lang.admin.inventory.products.form', $row->product_id);
        }
    
        $data['ingredients'] = $ingredients->withPath(route('lang.admin.sale.orderingredientsdailies.list'))->appends($query_data);

        // Prepare links for list table's header
        if($query_data['order'] == 'ASC'){
            $order = 'DESC';
        }else{
            $order = 'ASC';
        }
        
        $data['sort'] = strtolower($query_data['sort']);
        $data['order'] = strtolower($order);
    
        $query_data = $this->unsetUrlQueryData($query_data);
        
        
        // link of table header for sorting
        $url = '';
    
        foreach($query_data as $key => $value){
            $url .= "&$key=$value";
        }
    
        $route = route('lang.admin.sale.orderingredientsdailies.list');
    
        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_required_date'] = $route . "?sort=required_date&order=$order" .$url;
        $data['sort_product_id'] = $route . "?sort=product_id&order=$order" .$url;
        $data['sort_product_name'] = $route . "?sort=product_name&order=$order" .$url;
        $data['sort_supplier_product_code'] = $route . "?sort=supplier_product_code&order=$order" .$url;
        $data['sort_supplier_short_name'] = $route . "?sort=supplier_short_name&order=$order" .$url;
        
        $data['list_url'] = route('lang.admin.sale.orderingredientsdailies.list');
        
        return view('admin.sale.order_ingredient_daily_list', $data);
    }









}