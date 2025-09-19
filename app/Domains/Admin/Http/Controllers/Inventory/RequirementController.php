<?php

namespace App\Domains\Admin\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Domains\Admin\Services\Inventory\RequirementService;
use App\Repositories\Eloquent\Localization\LanguageRepository;
use App\Helpers\Classes\DateHelper;

class RequirementController extends BackendController
{
    public function __construct(private Request $request, private RequirementService $RequirementService)
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/inventory/requirement']);
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
            'text' => $this->lang->text_inventory,
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
        $data['export_list'] = route('lang.admin.inventory.materialRequirements.export_list');

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
        $query_data  = $this->url_data;

        // Rows
        $requirements = $this->RequirementService->getRequirementsDaily($query_data);

        foreach ($requirements as $row) {
            $row->edit_url = route('lang.admin.inventory.materialRequirements.form', array_merge([$row->id], $query_data));
            $row->is_active_name = ($row->is_active==1) ? $this->lang->text_enabled :$this->lang->text_disabled;
            $row->product_edit_url = route('lang.admin.inventory.products.form', $row->product_id);
        }

        $data['requirements'] = $requirements->withPath(route('lang.admin.inventory.materialRequirements.list'))->appends($query_data);

        // Prepare links for list table's header
        if(isset($query_data['order']) && $query_data['order'] == 'ASC'){
            $order = 'DESC';
        }else{
            $order = 'ASC';
        }

        $data['sort'] = strtolower($query_data['sort'] ?? '');
        $data['order'] = strtolower($order);

        $query_data = $this->unsetUrlQueryData($query_data);


        // link of table header for sorting
        $url = '';

        foreach($query_data as $key => $value){
            if(is_string($value)){
                $url .= "&$key=$value";
            }
        }

        $route = route('lang.admin.inventory.materialRequirements.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_required_date'] = $route . "?sort=required_date&order=$order" .$url;
        $data['sort_product_id'] = $route . "?sort=product_id&order=$order" .$url;
        $data['sort_product_name'] = $route . "?sort=product_name&order=$order" .$url;
        $data['sort_supplier_product_code'] = $route . "?sort=supplier_product_code&order=$order" .$url;
        $data['sort_supplier_short_name'] = $route . "?sort=supplier_short_name&order=$order" .$url;

        $data['list_url'] = route('lang.admin.inventory.materialRequirements.list');

        return view('admin.inventory.material_requirement_list', $data);
    }

    public function anylize()
    {
        $post_data = $this->request->all();

        if(!empty($post_data['filter_required_date'])){
            $result = DateHelper::parseDateOrPeriod($post_data['filter_required_date']);

            if(!empty($result['data'])){
                if(isset($result['data'])){
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

        $result = $this->RequirementService->anylize($filter_data);




    }

    public function exportList()
    {
        $post_data = request()->post();
        return $this->RequirementService->exportList($post_data);
    }


}
