<?php

namespace App\Domains\Admin\Services\Inventory;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Inventory\BomRepository;
use App\Traits\EloquentTrait;

class BomService
{
    use EloquentTrait;

    protected $modelName = "\App\Models\Inventory\Bom";

    public function __construct(protected BomRepository $BomRepository)
    {}


    public function getBoms($data=[], $debug = 0)
    {
        return $this->BomRepository->getRows($data, $debug);
    }


    public function getExtraColumns($row, $columns)
    {
        return $this->BomRepository->getExtraColumns($row, $columns);
    }



}