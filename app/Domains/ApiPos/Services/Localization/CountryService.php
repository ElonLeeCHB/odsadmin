<?php

namespace App\Domains\ApiPos\Services\Localization;

use App\Services\Localization\CountryService as GlobalCountryService;

class CountryService extends GlobalCountryService
{
    public $modelName = "\App\Models\Localization\Country";
}
