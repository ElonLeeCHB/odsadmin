<?php

namespace App\Domains\ApiV2\Services\Catalog;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Domains\ApiV2\Services\Service;
use App\Services\Catalog\OptionService as GlobalOptionService;
use App\Domains\ApiV2\Services\Catalog\OptionValueService;
use App\Libraries\TranslationLibrary;

class OptionService extends GlobalOptionService
{
    public $modelName = "\App\Models\Catalog\Option";
}
