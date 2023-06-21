<?php

namespace App\Domains\Api\Services\Common;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use App\Domains\Api\Services\Service;
use App\Traits\Model\EloquentTrait;
use App\Libraries\TranslationLibrary;

class TermService extends Service
{
    use EloquentTrait;

    public $modelName;
    public $model;
    public $table;
    public $lang;

	public function __construct()
	{        
        $this->modelName = "\App\Models\Common\Term";

        $groups = [
            'admin/common/common',
            'admin/common/term',
        ];
        $this->lang = (new TranslationLibrary())->getTranslations($groups);
	}
    

    public function getTerms($data=[], $debug = 0)
    {
        if(!empty($data['filter_name'])){
            $data['whereHas']['translation']['filter_name'] = $data['filter_name'];
            unset($data['filter_name']);
        }
    
        $records = $this->getModelCollection($data);

        return $records;
    }


    /**
     * $data['id] is necessary.
     */
    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {

            $data['id'] = $data['term_id'];

            $record = $this->findOrNew($data);

            $record->parent_id = $data['parent_id'] ?? 0;
            $record->code = $data['code'] ?? '';
            $record->slug = $data['slug'] ?? '';
            $record->taxonomy = $data['taxonomy'] ?? '';
            $record->is_active = $data['is_active'] ?? 0;
            $record->sort_order = $data['sort_order'] ?? 9999;

            $record->save();

            if(!empty($data['translations'])){
                $this->saveTranslationData($record, $data['translations']);
            }

            DB::commit();

            $result['data']['record_id'] = $record->id;
            
            return $result;
            
        } catch (\Exception $ex) {
            DB::rollback();
            $msg = $ex->getMessage();
            return response()->json(['error' => $msg], 500);
        }
        
        return false;
    }
}
