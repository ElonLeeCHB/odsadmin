<?php

namespace App\Domains\Admin\Http\Controllers\Catalog;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Repositories\Eloquent\Localization\LanguageRepository;
use App\Domains\Admin\Services\Catalog\TagService;

class TagController extends BackendController
{
    public function __construct(private Request $request, private LanguageRepository $LanguageRepository, private TagService $TagService)
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/common/term','admin/catalog/tag']);
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
            'href' => route('lang.admin.catalog.tags.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        $data['list'] = $this->getList();

        $data['list_url'] =route('lang.admin.catalog.tags.list'); //本參數在 getList() 也必須存在。
        $data['add_url'] = route('lang.admin.catalog.tags.form');
        $data['delete_url'] = route('lang.admin.catalog.tags.delete');

        return view('admin.catalog.tag', $data);
    }


    public function list()
    {
        return $this->getList();
    }


    public function getList()
    {
        $data['lang'] = $this->lang;

        // Prepare query_data for records
        $query_data  = $this->url_data;

        $query_data['equal_taxonomy_code'] = 'ProductTag';

        // rows
        $tags = $this->TagService->getTags($query_data);

        if(!empty($tags)){
            foreach ($tags as $row) {
                $row->edit_url = route('lang.admin.catalog.tags.form', array_merge([$row->id], $query_data));
            }
        }

        $data['tags'] = $tags->withPath(route('lang.admin.catalog.tags.list'))->appends($query_data);

        // Prepare links for list table's header
        if(isset($query_data['order']) && $query_data['order'] == 'ASC'){
            $order = 'DESC';
        }else{
            $order = 'ASC';
        }
        
        $data['sort'] = strtolower($query_data['sort'] ?? '');
        $data['order'] = strtolower($order);

        unset($query_data['sort']);
        unset($query_data['order']);
        unset($query_data['with']);

        $url = '';

        foreach($query_data as $key => $value){
            if(is_string($value)){
                $url .= "&$key=$value";
            }
        }
        
        // link of table header for sorting        
        $route = route('lang.admin.catalog.tags.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_code'] = $route . "?sort=code&order=$order" .$url;
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_short_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_taxonomy_name'] = $route . "?sort=taxonomy_name&order=$order" .$url;
        $data['sort_is_active'] = $route . "?sort=is_active&order=$order" .$url;
        $data['sort_date_added'] = $route . "?sort=created_at&order=$order" .$url;

        $data['list_url'] =route('lang.admin.catalog.tags.list');

        return view('admin.catalog.tag_list', $data);
    }


    public function form($tag_id = null)
    {
        $data['lang'] = $this->lang;
  
        $this->lang->text_form = empty($product_id) ? $this->lang->trans('text_add') : $this->lang->trans('text_edit');

        // Languages
        $data['languages'] = $this->LanguageRepository->newModel()->active()->get();

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
            'href' => route('lang.admin.catalog.products.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        // Prepare link for save, back
        $queries  = $this->url_data;

        $data['save_url'] = route('lang.admin.catalog.tags.save');
        $data['back_url'] = route('lang.admin.catalog.tags.index', $queries);   
        $data['autocomplete_url'] = route('lang.admin.catalog.tags.autocomplete');

        // Get Record
        $result = $this->TagService->findIdOrFailOrNew($tag_id,['equal_taxonomy_code' => 'ProductTag']);

        if(!empty($result['data'])){
            $tag = $result['data'];
        }else if(!empty($result['error'])){
            return response(json_encode(['error' => $result['error']]))->header('Content-Type','application/json');
        }
        unset($result);

        $data['tag']  = $tag;
        
        if(!empty($data['tag']) && $tag_id == $tag->id){
            $data['tag_id'] = $tag_id;
        }else{
            $data['tag_id'] = null;
        }

        // product_translations
        if($tag->translations->isEmpty()){
            $translations = [];
        }else{
            foreach ($tag->translations as $translation) {
                $translations[$translation->locale] = $translation->toArray();
            }
        }
        $data['translations'] = $translations;

        $data['taxonomy_code'] = 'ProductTag';
        
        return view('admin.catalog.tag_form', $data);
    }


    public function save()
    {
        $data = $this->request->all();

        $json = [];

        if (isset($json['error']) && !isset($json['error']['warning'])) {
            $json['error']['warning'] = $this->lang->error_warning;
        }

        if(!$json) {
            
            $data['taxonomy_code'] = 'ProductTag';

            $result = $this->TagService->updateOrCreateTag($data);
            if(empty($result['error'])){
                $json = [
                    'tag_id' => $result['term_id'],
                    'success' => $this->lang->text_success,
                    'redirectUrl' => route('lang.admin.catalog.tags.form', $result['term_id']),
                ];
            }else if(auth()->user()->username == 'admin'){
                $json['warning'] = $result['error'];
            }else{
                $json['warning'] = $this->lang->text_fail;
            }
        }
        
        return response(json_encode($json))->header('Content-Type','application/json');

    }

    public function delete()
    {
        $post_data = $this->request->post();

		$json = [];

		if (isset($post_data['selected'])) {
			$selected = $post_data['selected'];
		} else {
			$selected = [];
		}

        // if (!$this->user->hasPermission('modify', 'catalog/category')) {
		// 	$json['error'] = $this->language->get('error_permission');
		// }

		if (!$json) {
			foreach ($selected as $tag_id) {
				$result = $this->TagService->deleteTagById($tag_id);

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
        $query_data  = $this->url_data;

        $query_data['pagination'] = false;

        // Rows
        $rows = $this->TagService->getTags($query_data);

        $json = [];

        foreach ($rows as $row) {
            if(!empty($query_data['exclude_id']) && $query_data['exclude_id'] == $row->id){
                continue;
            }
            $json[] = array(
                'tag_id' => $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'short_name' => $row->short_name,
                'parent_name' => $row->parent_name ?? '',
            );
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }
}