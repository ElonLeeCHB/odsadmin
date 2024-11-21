<?php

namespace App\Domains\ApiWww\Services\Sale;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Domains\ApiWww\Services\Service;
use App\Traits\Model\EloquentTrait;
use App\Libraries\TranslationLibrary;

class OrderProductOptionService extends Service
{
    use EloquentTrait;

    public $modelName;
    public $model;
    public $table;

	public function __construct()
	{
        $this->modelName = "\App\Models\Sale\OrderProductOption";
	}
}
