<?php

namespace App\Domains\Admin\Services\SysData;

use App\Libraries\TranslationLibrary;
use App\Services\Service;
use App\Repositories\Eloquent\SysData\GovUniformInvoiceNumberRepository;
use Cache;

class FinancialInstitutionService extends Service
{
    protected $modelName = "\App\Models\SysData\GovUniformInvoiceNumber";
    protected $connection = 'sysdata';


}