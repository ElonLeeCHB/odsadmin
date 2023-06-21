<?php

namespace App\Domains\Api\Services\Common;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Domains\Api\Services\Service;
use App\Domains\Api\Services\Common\OptionValueService;
use App\Traits\Model\EloquentTrait;
use App\Libraries\TranslationLibrary;

class OptionService extends Service
{
    use EloquentTrait;

    public $modelName;
    public $model;
    public $table;
    public $lang;

	public function __construct(private OptionValueService $OptionValueService)
	{
        $this->modelName = "\App\Models\Common\Option";

        $groups = [
            'admin/common/common',
            'admin/common/option',
        ];
        $this->lang = (new TranslationLibrary())->getTranslations($groups);
	}
    

    public function getOptions($data=[], $debug = 0)
    {
        $records = $this->getRecords($data, $debug);
    
        return $records;
    }
    

    public function getOption($data=[], $debug = 0)
    {    
        return $this->getRecord($data, $debug);
    }


    public function getValues($data, $debug=0)
    {
        $option_values = $this->OptionValueService->getRecords($data, $debug);

        return $option_values;
    }


    public function validator(array $data)
    {
        foreach ($data['option_translations'] as $lang_code => $value) {
            $key = 'name-'.$lang_code;
            $arr[$key] = $value['name'];
            $arr1[$key] = 'required|max:200';
            $arr2[$key] = $this->lang->error_name;
        }

        return Validator::make($arr, $arr1,$arr2);
    }
}