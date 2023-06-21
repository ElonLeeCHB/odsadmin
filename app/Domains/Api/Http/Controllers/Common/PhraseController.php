<?php

namespace App\Domains\Api\Http\Controllers\Common;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\TranslationLibrary;
use App\Services\Common\TermService;

class PhraseController extends Controller
{
    public function __construct(
        private Request $request
        , private TermService $TermService
        )
    {}


    public function list()
    {
        $queries = [];

        $queries['filter_taxonomy'] = "phrase_*";

        if(!empty($this->request->query('page'))){
            $queries['page'] = $this->request->input('page');
        }else{
            $queries['page'] = 1;
        }

        if(!empty($this->request->query('sort'))){
            $queries['sort'] = $this->request->input('sort');
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

        foreach($this->request->all() as $key => $value){
            if(strpos($key, 'filter_') !== false){
                $queries[$key] = $value;
            }
        }

        $phrases = $this->TermService->getRows($queries);

        return response(json_encode($phrases))->header('Content-Type','application/json');
    }


    public function details($phrase_id)
    {
        $phrase = $this->TermService->find($phrase_id);

        return response(json_encode($phrase))->header('Content-Type','application/json');
    }
}
