<?php

namespace App\Domains\Admin\Http\Controllers\Common;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\Eloquent\Localization\LanguageRepository;
use App\Libraries\TranslationLibrary;
use App\Domains\Admin\Services\Common\TermService;

class PhraseController extends Controller
{
    private $request;
    private $LanguageRepository;
    private $TermService;
    private $lang;

    public function __construct(
        Request $request
        , TermService $TermService
        , LanguageRepository $LanguageRepository
        )
    {
        $this->request = $request;
        $this->TermService = $TermService;
        $this->LanguageRepository = $LanguageRepository;
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/common/common','admin/common/phrase']);
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
            'href' => route('lang.admin.common.phrases.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;
        
        $data['list'] = $this->getList();

        return view('admin.common.phrase', $data);
    }

    public function list()
    {
        $data['lang'] = $this->lang;
        
        $data['form_action'] = route('lang.admin.common.phrases.list');

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

        // Prepare link for action
        $queries = [];

        if(!empty($this->request->query('page'))){
            $page = $queries['page'] = $this->request->input('page');
        }else{
            $page = $queries['page'] = 1;
        }

        if(!empty($this->request->query('sort'))){
            $sort = $queries['sort'] = $this->request->input('sort');
        }else{
            $sort = $queries['sort'] = 'id';
        }

        if(!empty($this->request->query('order'))){
            $order = $queries['order'] = $this->request->query('order');
        }else{
            $order = $queries['order'] = 'DESC';
        }

        if(empty($this->request->query('filter_taxonomy'))){
            $queries['filter_taxonomy'] = "phrase*";
        }

        if(!empty($this->request->query('limit'))){
            $limit = $queries['limit'] = $this->request->query('limit');
        }

        foreach($this->request->all() as $key => $value){
            if(strpos($key, 'filter_') !== false){
                $queries[$key] = $value;
            }
        }

        // Rows
        $phrases = $this->TermService->getRows($queries);

        if(count($phrases)>0){
            foreach ($phrases as $key => $phrase) {
                $phrase->edit_url = route('lang.admin.common.phrases.form', array_merge([$phrase->id], $queries));
            }
        }

        $data['phrases'] = $phrases->withPath(route('lang.admin.common.phrases.list'))->appends($queries);

        // Prepare links for list table's header
        if($order == 'ASC'){
            $order = 'DESC';
        }else{
            $order = 'ASC';
        }
        
        $data['sort'] = strtolower($sort);
        $data['order'] = strtolower($order);

        unset($queries['sort']);
        unset($queries['order']);

        $url = '';

        foreach($queries as $key => $value){
            $url .= "&$key=$value";
        }
        
        // link of table header for sorting        
        $route = route('lang.admin.common.phrases.list');
        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_email'] = $route . "?sort=email&order=$order" .$url;
        $data['sort_date_added'] = $route . "?sort=created_at&order=$order" .$url;

        return view('admin.common.phrase_list', $data);
    }


    public function form($phrase_id = null)
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
            'href' => route('lang.admin.common.phrases.index'),
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

        $data['save'] = route('lang.admin.common.phrases.save');
        $data['back'] = route('lang.admin.common.phrases.index', $queries);

        // Get Record
        $phrase = $this->TermService->findIdOrFailOrNew($phrase_id);

        if(!empty($phrase)){
            $data['phrase_id'] = $phrase_id;
        }else{
            $data['phrase_id'] = null;
        }
        
        $data['phrase']  = $phrase;
        
        // translations
        if($phrase->translations->isEmpty()){
            $phrase_translations = [];
        }else{
            foreach ($phrase->translations as $translation) {
                $phrase_translations[$translation->locale] = $translation->toArray();
            }
        }
        $data['phrase_translations'] = $phrase_translations;

        return view('admin.common.phrase_form', $data);
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
            $data['term_id'] = $data['phrase_id'] ?? '';
            $result = $this->TermService->updateOrCreate($data);

            if(empty($result['error']) && !empty($result['term_id'])){
                $json = [
                    'term_id' => $result['term_id'],
                    'success' => $this->lang->text_success,
                    'redirectUrl' => route('lang.admin.common.phrases.form', $result['term_id']),
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

            $data['id'] = $data['phrase_id'];            
            $result = $this->TermService->updateOrCreate($data);
            
            if(empty($result['error'])){
                $json['redirectUrl'] = route('lang.admin.common.phrases.form', $result['data']['record_id']);
                $json['phrase_id'] = $result['data']['record_id'];
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
    

    public function autocomplete()
    {
        $json = [];

        //$filter_data['filter_id'] = '>1';

        if(isset($this->request->filter_personal_name) && mb_strlen($this->request->filter_personal_name, 'utf-8') > 1)
        {
            $filter_data['filter_name'] = $this->request->filter_personal_name;
        }

        if(isset($this->request->filter_mobile) && strlen($this->request->filter_mobile) > 2)
        {
            $filter_data['filter_mobile'] = $this->request->filter_mobile;
        }

        if(isset($this->request->filter_telephone) && strlen($this->request->filter_telephone) > 2)
        {
            $filter_data['filter_telephone'] = $this->request->filter_telephone;
        }

        if(isset($this->request->filter_email) && strlen($this->request->filter_email) > 2)
        {
            $filter_data['filter_email'] = $this->request->filter_email;
        }

        if (empty($this->request->sort)) {
            $filter_data['sort'] = 'name';
            $filter_data['order'] = 'ASC';
        }else{
            $filter_data['sort'] = $this->request->sort;
            $filter_data['order'] = $this->request->order;
        }

        if(!empty($this->request->with) )
        {
            $filter_data['with'] = $this->request->with;
        }

        $filter_data['pagination'] = false;

        $phrases = $this->TermService->getRows($filter_data);

        foreach ($phrases as $row) {
            $row = $this->TermService->parseShippingAddress($row);

            $show_text = '';
            if(!empty($this->request->show_column1) && !empty($this->request->show_column2)){
                $col = $this->request->show_column1;
                $show_text = $row->$col;

                $col = $this->request->show_column2;
                $show_text .= '_'.$row->$col;
            }else{
                $show_text = $row->personal_name . '_' . $row->mobile;
            }

            $has_order = 0;
            if(count($row->orders)){
                $has_order = 1;
            }

            $json[] = array(
                'label' => $show_text,
                'value' => $row->id,
                'customer_id' => $row->id,
                'personal_name' => $row->name,
                'salutation_id' => $row->salutation_id,
                'telephone' => $row->telephone,
                'mobile' => $row->mobile,
                'email' => $row->email,
                'payment_company' => $row->payment_company,
                'payment_department' => $row->payment_department,
                'payment_tin' => $row->payment_tin,                
                'shipping_personal_name' => $row->shipping_personal_name,
                'shipping_phone' => $row->shipping_phone,
                'shipping_company' => $row->shipping_company,
                'shipping_state_id' => $row->shipping_state_id,
                'shipping_city_id' => $row->shipping_city_id,
                'shipping_road_id' => $row->shipping_road_id,
                'shipping_road' => $row->shipping_road,
                'shipping_address1' => $row->shipping_address1,  
                'shipping_lane' => $row->shipping_lane,
                'shipping_alley' => $row->shipping_alley,
                'shipping_no' => $row->shipping_no,
                'shipping_floor' => $row->shipping_floor,
                'shipping_room' => $row->shipping_room,
                'has_order' => $has_order,
            );
        }
        
        array_unshift($json,[
            'value' => 0, 
            'label' => ' -- ',
            'customer_id' => '',
            'personal_name' => '',
            'salutation_id' => '',
            'telephone' => '',
            'mobile' => '',
            'email' => '',
            'payment_company' => '',
            'payment_department' => '',
            'payment_tin' => '',           
            'shipping_personal_name' => '',
            'shipping_phone' => '',
            'shipping_company' => '',
            'shipping_state_id' => '',
            'shipping_city_id' => '',
            'shipping_road_id' => '',
            'shipping_road' => '',
            'shipping_address1' => '',
            'shipping_lane' => '',
            'shipping_alley' => '',
            'shipping_no' => '',
            'shipping_floor' => '',
            'shipping_room' => '',
        ]);

        return response(json_encode($json))->header('Content-Type','application/json');
    }
}