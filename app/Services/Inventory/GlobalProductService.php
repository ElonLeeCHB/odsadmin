<?php

namespace App\Services\Inventory;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Models\Common\TermRelation;
use App\Models\Catalog\ProductOption;
use App\Models\Catalog\ProductOptionValue;
use App\Repositories\Eloquent\Catalog\ProductRepository;

class GlobalProductService extends Service
{
    public $modelName = "\App\Models\Catalog\Product";


    public function __construct(private ProductRepository $ProductRepository)
    {}


}
