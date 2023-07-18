<?php

namespace App\Domains\Admin\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Repositories\Eloquent\Localization\LanguageRepository;
use App\Domains\Admin\Services\Common\TermService;

class PhraseController extends BackendController
{
    public function __construct(private Request $request, private TermService $TermService, private LanguageRepository $LanguageRepository)
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/common/term','admin/common/phrase']);
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
            'text' => $this->lang->text_phrase,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];
        
        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.sale.phrases.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;
        
        $data['list'] = $this->getList();

        $data['list_url'] =route('lang.admin.sale.phrases.list');
        $data['add_url'] = route('lang.admin.sale.phrases.form');
        $data['delete_url'] = route('lang.admin.sale.phrases.delete');

        return view('admin.sale.phrase', $data);
    }

    public function list()
    {
        $data['lang'] = $this->lang;
        
        $data['form_action'] = route('lang.admin.sale.phrases.list');

        return $this->getList();
    }

    /**
     * Show the list table.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    private function getList()
    {
        $data['lang'] = $this->lang;

        // Prepare link for action
        $queries = $this->getQueries($this->request->query());

        $queries['whereIn'] = ['taxonomy_code' => ['phrase_order_comment', 'phrase_order_extra_comment']];

        // Rows
        $phrases = $this->TermService->getRows($queries);

        if(count($phrases)>0){
            foreach ($phrases as $key => $phrase) {
                $phrase->edit_url = route('lang.admin.sale.phrases.form', array_merge([$phrase->id], $queries));
            }
        }

        $data['phrases'] = $phrases->withPath(route('lang.admin.sale.phrases.list'))->appends($queries);

        // Prepare links for list table's header
        if($queries['order'] == 'ASC'){
            $order = 'DESC';
        }else{
            $order = 'ASC';
        }
        
        $data['sort'] = strtolower($queries['sort']);
        $data['order'] = strtolower($order);

        unset($queries['sort']);
        unset($queries['order']);
        unset($queries['with']);
        unset($queries['whereIn']);

        $url = '';

        foreach($queries as $key => $value){
            $url .= "&$key=$value";
        }
        
        // link of table header for sorting        
        $route = route('lang.admin.sale.phrases.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_email'] = $route . "?sort=email&order=$order" .$url;
        $data['sort_date_added'] = $route . "?sort=created_at&order=$order" .$url;

        $data['list_url'] = route('lang.admin.sale.phrases.list');

        return view('admin.sale.phrase_list', $data);
    }


    public function form($term_id = null)
    {
        $data['lang'] = $this->lang;
  
        $this->lang->text_form = empty($catalog_id) ? $this->lang->trans('text_add') : $this->lang->trans('text_edit');

        // Languages
        $data['languages'] = $this->LanguageRepository->newModel()->active()->get();

        // Breadcomb
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_home,
            'href' => route('lang.admin.dashboard'),
        ];
        
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_catalog,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];
        
        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.sale.phrases.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        // Prepare link for save, back
        $queries = $this->getQueries($this->request->query());

        $data['save_url'] = route('lang.admin.sale.phrases.save');
        $data['back_url'] = route('lang.admin.sale.phrases.index', $queries);
        $data['autocomplete_url'] = route('lang.admin.sale.phrases.autocomplete');

        // Get Record
        $term = $this->TermService->findIdOrFailOrNew($term_id);

        if(!empty($term)){
            $data['term_id'] = $term_id;
        }else{
            $data['term_id'] = null;
        }
        
        $data['term']  = $term;
        
        // translations
        if($term->translations->isEmpty()){
            $translations = [];
        }else{
            foreach ($term->translations as $translation) {
                $translations[$translation->locale] = $translation->toArray();
            }
        }
        $data['translations'] = $translations;

        return view('admin.sale.phrase_form', $data);
    }
    

    public function save()
    {
        $data = $this->request->all();

        $json = [];

        // Check catalog
        // $validator = $this->TermService->validator($this->request->post());

        // if($validator->fails()){
        //     $messages = $validator->errors()->toArray();
        //     foreach ($messages as $key => $rows) {
        //         $json['error'][$key] = $rows[0];
        //     }
        // }

        if(isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = $this->lang->error_warning;
        }

        if(!$json) {
            $data['term_id'] = $data['term_id'] ?? '';
            $result = $this->TermService->updateOrCreate($data);

            if(empty($result['error']) && !empty($result['term_id'])){
                $json = [
                    'term_id' => $result['term_id'],
                    'success' => $this->lang->text_success,
                    'redirectUrl' => route('lang.admin.sale.phrases.form', $result['term_id']),
                ];
            }else{
                if(config('app.debug')){
                    $json['error'] = $result['error'];
                }else{
                    $json['error'] = $this->lang->text_fail;
                }
            }
        }






        if(!$json) {

            $data['id'] = $data['term_id'];            
            $result = $this->TermService->updateOrCreate($data);
            
            if(empty($result['error'])){
                $json['redirectUrl'] = route('lang.admin.sale.phrases.form', $result['data']['record_id']);
                $json['term_id'] = $result['data']['record_id'];
                $json['success'] = $this->lang->text_success;
            }else{
                $username = auth()->user()->username;
                if($username == 'admin'){
                    $json['error'] = $result['error'];
                }else{
                    $json['error'] = $this->lang->text_fail;
                }
            }
        }
        
       return response(json_encode($json))->header('Content-Type','application/json');
    }
    

    public function delete()
    {
        $this->initController();
        
        $post_data = $this->request->post();

        $json = [];

        if (isset($post_data['selected'])) {
            $selected = $post_data['selected'];
        } else {
            $selected = [];
        }

        // Permission
        if($this->acting_username !== 'admin'){
            $json['error'] = $this->lang->error_permission;
        }

        if (!$json) {
            foreach ($selected as $category_id) {
                $result = $this->TermService->deleteTerm($category_id);

                if(!empty($result['error'])){
                    if(config('app.debug')){
                        $json['warning'] = $result['error'];
                    }else{
                        $json['warning'] = $this->lang->text_fail;
                    }

                    break;
                }
            }
        }

        if(empty($json['warning'] )){
            $json['success'] = $this->lang->text_success;
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }


    public function autocomplete()
    {
        $queries = $this->getQueries($this->request->query());

        $queries['pagination'] = false;
        $queries['whereIn'] = ['taxonomy_code' => ['phrase_order_comment','phrase_order_extra_comment']];

        // Rows
        $rows = $this->TermService->getRows($queries);

        $json = [];

        foreach ($rows as $row) {
            $json[] = array(
                'term_id' => $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'short_name' => $row->short_name,
                'parent_name' => $row->parent_name ?? '',
            );
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }
}