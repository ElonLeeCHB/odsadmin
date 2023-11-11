<?php

namespace App\Domains\Admin\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Domains\Admin\Services\Inventory\MaterialRequirementService;
use App\Repositories\Eloquent\Localization\LanguageRepository;
use App\Helpers\Classes\DateHelper;

class MaterialRequirementController extends BackendController
{
    public function __construct(private Request $request, private MaterialRequirementService $MaterialRequirementService)
    {
        parent::__construct();
        
        $this->getLang(['admin/common/common','admin/inventory/material_requirement']);
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
            'text' => $this->lang->text_menu_inventory,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];
        
        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.inventory.materialRequirements.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        $data['list'] = $this->getList();

        $data['list_url'] = route('lang.admin.inventory.materialRequirements.list'); //本參數在 getList() 也必須存在。
        $data['add_url'] = route('lang.admin.inventory.materialRequirements.form');
        $data['delete_url'] = route('lang.admin.inventory.materialRequirements.delete');
        $data['anylize_url'] = route('lang.admin.inventory.materialRequirements.anylize');
        
        return view('admin.inventory.material_requirement', $data);
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
        $requirements = $this->MaterialRequirementService->getRequirementsDaily($query_data);

        foreach ($requirements as $row) {
            $row->edit_url = route('lang.admin.inventory.materialRequirements.form', array_merge([$row->id], $query_data));
            $row->is_active_name = ($row->is_active==1) ? $this->lang->text_enabled :$this->lang->text_disabled;
        }

        $data['requirements'] = $requirements->withPath(route('lang.admin.inventory.materialRequirements.list'))->appends($query_data);

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

        $route = route('lang.admin.inventory.materialRequirements.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_required_date'] = $route . "?sort=required_date&order=$order" .$url;
        $data['sort_product_id'] = $route . "?sort=product_id&order=$order" .$url;
        $data['sort_product_name'] = $route . "?sort=product_name&order=$order" .$url;
        $data['sort_supplier_product_code'] = $route . "?sort=supplier_product_code&order=$order" .$url;
        
        $data['list_url'] = route('lang.admin.inventory.materialRequirements.list');
        
        return view('admin.inventory.material_requirement_list', $data);
    }

    public function form($unit_id = null)
    {
        $data['lang'] = $this->lang;

        // Languages
        $data['languages'] = $this->LanguageRepository->newModel()->active()->get();

        $this->lang->text_form = empty($unit_id) ? $this->lang->trans('text_add') : $this->lang->trans('text_edit');

        // Breadcomb
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_home,
            'href' => route('lang.admin.dashboard'),
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->text_warehouse,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.inventory.units.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        // Prepare link for save, back
        $queries = [];

        foreach($this->request->all() as $key => $value){
            if(strpos($key, 'filter_') !== false){
                $queries[$key] = $value;
            }
        }

        if(!empty($this->request->query('page'))){
            $queries['page'] = $this->request->query('page');
        }else{
            $queries['page'] = 1;
        }

        if(!empty($this->request->query('sort'))){
            $queries['sort'] = $this->request->query('sort');
        }else{
            $queries['sort'] = 'id';
        }

        if(!empty($this->request->query('order'))){
            $queries['order'] = $this->request->query('order');
        }else{
            $queries['order'] = 'DESC';
        }

        if(!empty($this->request->query('limit'))){
            $queries['limit'] = $this->request->query('limit');
        }

        $data['save_url'] = route('lang.admin.inventory.units.save');
        $data['back_url'] = route('lang.admin.inventory.units.index', $queries);        

        // Get Record
        $unit = $this->UnitService->findIdOrFailOrNew($unit_id);


        // translations
        if($unit->translations->isEmpty()){
            $translations = [];
        }else{
            foreach ($unit->translations as $translation) {
                $translations[$translation->locale] = $translation->toArray();
            }
        }
        $data['translations'] = $translations;
        
        $data['unit']  = $this->UnitService->sanitizeRow($unit);

        if(!empty($data['unit']) && $unit_id == $unit->id){
            $data['unit_id'] = $unit_id;
        }else{
            $data['unit_id'] = null;
        }

        return view('admin.inventory.material_requirement_form', $data);
    }

    public function anylize()
    {
        $post_data = $this->request->all();

        if(!empty($post_data['filter_required_date'])){
            $result = DateHelper::parseDate($post_data['filter_required_date']);

            if(!empty($result['data'])){
                if(isset($result['data']) && is_array($result['data'])){
                    $date1 = $result['data'][0];
                    $date2 = $result['data'][1] ?? '';
                }
            }
        }

        if(empty($date1)){
            return false;
        }

        $filter_data = [
            'date1' => $date1,
            'date2' => $date2,
        ];

        $result = $this->MaterialRequirementService->anylize($filter_data);







        echo '<pre>', print_r($date1, 1), "</pre>";
        echo '<pre>', print_r($date2, 1), "</pre>";


    }
    

}