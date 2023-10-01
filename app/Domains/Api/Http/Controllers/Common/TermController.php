<?php

namespace App\Domains\Api\Http\Controllers\Common;

use Illuminate\Http\Request;
use App\Domains\Api\Http\Controllers\ApiController;
use App\Domains\Api\Services\Common\TermService;

class TermController extends ApiController
{
    public function __construct(private Request $request, private TermService $TermService)
    {
        parent::__construct();
    }


    public function list()
    {
        $query_data = $this->request->query();

        $filter_data = $this->getQueries($query_data);

        $phrases = $this->TermService->getTerms($filter_data);
        
        $phrases = $this->TermService->unsetRelations($phrases, ['translation', 'taxonomy']);
        
        return response(json_encode($phrases))->header('Content-Type','application/json');
    }


    public function details($phrase_id)
    {
        $phrase = $this->TermService->findIdFirst($phrase_id);

        $phrase = $this->TermService->sanitizeRow($phrase);

        return response(json_encode($phrase))->header('Content-Type','application/json');
    }
}
