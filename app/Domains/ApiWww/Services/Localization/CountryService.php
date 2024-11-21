<?php

namespace App\Domains\ApiWww\Services\Localization;

use App\Services\Localization\CountryService as GlobalCountryService;

class CountryService extends GlobalCountryService
{
    public $modelName = "\App\Models\Localization\Country";
}
