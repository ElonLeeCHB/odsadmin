<?php

namespace App\Domains\Api\Services\Localization;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Domains\Api\Services\Service;
use App\Traits\EloquentTrait;
use App\Libraries\TranslationLibrary;
//use App\Models\Localization\RoadFirstWord;
use App\Repositories\Eloquent\Localization\RoadFirstWordRepository;

class RoadService extends Service
{
    use EloquentTrait;

    public $modelName;
    public $model;
    public $table;

	public function __construct(private RoadFirstWordRepository $RoadFirstWordRepository)
	{
        $this->modelName = "\App\Models\Localization\Road";
	}


    public function getFirstWords($data, $debug=0)
    {
        $rows = $this->RoadFirstWordRepository->getRows($data, $debug);

        return $rows;
    }
}