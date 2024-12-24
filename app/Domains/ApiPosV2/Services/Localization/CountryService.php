<?php

namespace App\Domains\ApiPosV2\Services\Localization;

use App\Services\Localization\CountryService as GlobalCountryService;

class CountryService extends GlobalCountryService
{
    public $modelName = "\App\Models\Localization\Country";
}
