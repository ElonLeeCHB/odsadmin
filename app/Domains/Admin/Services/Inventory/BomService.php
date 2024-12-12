<?php

namespace App\Domains\Admin\Services\Inventory;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Inventory\BomRepository;
use App\Traits\Model\EloquentTrait;

class BomService
{
    use EloquentTrait;

    protected $modelName = "\App\Models\Inventory\Bom";

    public function __construct(protected BomRepository $BomRepository)
    {}


    public function getBoms($data=[], $debug = 0)
    {
        return $this->BomRepository->getBoms($data, $debug);
    }


    public function getBomSubProducts($bom)
    {
        return $this->BomRepository->getBomSubProducts($bom);
    }


    public function getExtraColumns($row, $columns)
    {
        return $this->BomRepository->getExtraColumns($row, $columns);
    }


    public function saveBom($post_data)
    {
        return $this->BomRepository->saveBom($post_data);
    }


    public function saveBomProducts($data)
    {
        return $this->BomRepository->saveBomProducts($data);
    }



}