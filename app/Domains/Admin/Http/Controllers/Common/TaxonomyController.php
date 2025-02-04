<?php

namespace App\Domains\Admin\Http\Controllers\Common;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Repositories\Eloquent\Localization\LanguageRepository;
use App\Domains\Admin\Services\Common\TaxonomyService;

class TaxonomyController extends BackendController
{
    public function __construct(
        private Request $request
        , private LanguageRepository $languageRepository
        , private TaxonomyService $TaxonomyService
    )
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/common/taxonomy']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['lang'] = $this->lang;

        // Breadcomb
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_home,
            'href' => route('lang.admin.dashboard'),
        ];
        
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_common,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];
        
        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.common.taxonomies.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        $data['list'] = $this->getList();

        $data['list_url'] = route('lang.admin.common.taxonomies.list'); //本參數在 getList() 也必須存在。
        $data['add_url'] = route('lang.admin.common.taxonomies.form');
        $data['delete_url'] = route('lang.admin.common.taxonomies.delete');

        return view('admin.common.taxonomy', $data);
    }

    public function list()
    {
        return $this->getList();
    }

    /**
     * Show the list table.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function getList()
    {
        $data['lang'] = $this->lang;

        // Prepare query_data for records
        $queries = $this->resetUrlData(request()->query());

        // Rows
        $taxonomies = $this->TaxonomyService->getRows($queries);

        if(!empty($taxonomies)){
            foreach ($taxonomies as $row) {
                $row->edit_url = route('lang.admin.common.taxonomies.form', array_merge([$row->id], $queries));
                unset($row->translation);
            }
        }

        $data['taxonomies'] = $taxonomies->withPath(route('lang.admin.common.taxonomies.list'))->appends($queries);

        // Prepare links for list table's header
        if($queries['order'] == 'ASC'){
            $order = 'DESC';
        }else{
            $order = 'ASC';
        }
        
        $data['sort'] = strtolower($queries['sort']);
        $data['order'] = strtolower($order);

        $url = '';

        foreach($queries as $key => $value){
            $url .= "&$key=$value";
        }
        
        // link of table header for sorting        
        $route = route('lang.admin.common.taxonomies.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_code'] = $route . "?sort=code&order=$order" .$url;
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_taxonomy'] = $route . "?sort=taxonomy&order=$order" .$url;

        $data['list_url'] = route('lang.admin.common.taxonomies.list'); 

        return view('admin.common.taxonomy_list', $data);
    }


    public function form($taxonomy_id = null)
    {
        $data['lang'] = $this->lang;

        // Languages dropdown menu
        $data['languages'] = $this->languageRepository->newModel()->active()->get();
  
        $this->lang->text_form = empty($product_id) ? $this->lang->trans('text_add') : $this->lang->trans('text_edit');

        // Breadcomb
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_home,
            'href' => route('lang.admin.dashboard'),
        ];
        
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_common,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];
        
        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.common.taxonomies.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        // Prepare link for save, back
        $queries = $this->resetUrlData(request()->query());

        $data['save_url'] = route('lang.admin.common.taxonomies.save');
        $data['back_url'] = route('lang.admin.common.taxonomies.index', $queries);

        // Get Record
        $taxonomy = $this->TaxonomyService->findIdOrNew($taxonomy_id);

        $data['taxonomy']  = $taxonomy;

        if(!empty($data['taxonomy']) && $taxonomy_id == $taxonomy->id){
            $data['taxonomy_id'] = $taxonomy_id;
        }else{
            $data['taxonomy_id'] = null;
        }

        // taxonomy_translations
        if($taxonomy->translations->isEmpty()){
            $taxonomy_translations = [];
        }else{
            foreach ($taxonomy->translations as $translation) {
                $taxonomy_translations[$translation->locale] = $translation->toArray();
            }
        }
        $data['taxonomy_translations'] = $taxonomy_translations;


        return view('admin.common.taxonomy_form', $data);
    }


    public function save()
    {
        $post_data = $this->request->all();

        $json = [];

        if (isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = $this->lang->error_warning;
        }

        if(!$json) {
            $result = $this->TaxonomyService->saveTaxonomy($post_data);

            // Success
            if(empty($result['error'])){
                $json = [
                    'taxonomy_id' => $result['taxonomy_id'],
                    'success' => $this->lang->text_success,
                    'redirectUrl' => route('lang.admin.common.taxonomies.form', $result['taxonomy_id']),
                ];
            }
            // Fails
            else{
                if(auth()->user()->username == 'admin'){
                    $json['error'] = $result['error'];
                }else{
                    $json['error'] = $this->lang->text_fail;
                }
            }
        }
        
        return response(json_encode($json))->header('Content-Type','application/json');

    }


    public function autocomplete()
    {
        $query_data = $this->resetUrlData(request()->query());

        if(!empty($query_data['equal_code'])){
            if (strpos($query_data['equal_code'], ',') !== false) {
                $arr = explode(',', $query_data['equal_code']);
                $query_data['whereIn'] = ['code' => $arr];
                unset($query_data['equal_code']);
            }
        }

        $rows = $this->TaxonomyService->getRows($query_data);

        $json = [];

        foreach ($rows as $row) {
            $json[] = array(
                'taxonomy_id' => $row->id,
                'code' => $row->code,
                'name' => $row->name,
            );
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }

}