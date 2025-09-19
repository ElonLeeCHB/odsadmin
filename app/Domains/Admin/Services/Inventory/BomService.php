<?php

namespace App\Domains\Admin\Services\Inventory;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Inventory\BomRepository;
use App\Traits\Model\EloquentTrait;
use App\Helpers\Classes\OrmHelper;
use App\Models\Inventory\Bom;

class BomService
{
    use EloquentTrait;

    protected $modelName = "\App\Models\Inventory\Bom";

    public function __construct(protected BomRepository $BomRepository)
    {}


    public function getBoms($params = [], $debug = 0)
    {
        $builder = Bom::query();

        $builder->withWhereHas('translation', function ($qry) use ($params) {
            $qry->select(['product_id', 'name']);
            if (!empty($params['filter_product_name'])) {
                OrmHelper::filterOrEqualColumn($qry, 'filter_name', $params['filter_product_name']);
            }
        });
        unset($params['filter_product_name']);

        if(!empty($params['filter_sub_product_name'])){
            $builder->whereHas('bomProducts.translation', function ($qry) use ($params) {
                OrmHelper::filterOrEqualColumn($qry, 'filter_name', $params['filter_sub_product_name']);
            });
            unset($params['filter_sub_product_name']);
        }

        OrmHelper::prepare($builder, $params);

        return OrmHelper::getResult($builder, $params);
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
        try {
            DB::beginTransaction();

            $bom = $this->BomRepository->saveBomBundle($post_data);

            DB::commit();

            return $bom;

        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }


    public function saveBomProducts($data)
    {
        try {
            DB::beginTransaction();

            return $this->BomRepository->saveBomProducts($data);

            DB::commit();

        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}