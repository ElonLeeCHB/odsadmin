<?php

namespace App\Domains\Api\Services\Common;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Traits\Model\EloquentTrait;
use App\Libraries\TranslationLibrary;
use App\Domains\Api\Services\Service;

class OptionValueService extends Service
{
    use EloquentTrait;

    public $modelName;
    public $model;
    public $table;
    public $lang;

	public function __construct()
	{
        $this->modelName = "\App\Models\Common\OptionValue";

        $groups = [
            'admin/common/common',
            'admin/common/option',
        ];
        $this->lang = (new TranslationLibrary())->getTranslations($groups);
	}
}