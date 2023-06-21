<?php

namespace App\Domains\Api\Services\Localization;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Domains\Api\Services\Service;
use App\Traits\Model\EloquentTrait;
use App\Libraries\TranslationLibrary;

class RoadService extends Service
{
    use EloquentTrait;

    public $modelName;
    public $model;
    public $table;

	public function __construct()
	{
        $this->modelName = "\App\Models\Localization\Road";
	}
}