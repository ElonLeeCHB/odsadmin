<?php

namespace App\Domains\ApiV2\Services\Sale;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Libraries\TranslationLibrary;
use App\Traits\Model\EloquentTrait;
use App\Domains\ApiV2\Services\Service;

class OrderProductService extends Service
{
    use EloquentTrait;

    public $modelName;
    public $model;
    public $table;
    public $lang;

	public function __construct()
	{
        $this->modelName = "\App\Models\Sale\OrderProduct";
        $this->model = new $this->modelName;
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/sale/order',]);
    }
}

