<?php

namespace App\Domains\Admin\Services\Sale;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Libraries\TranslationLibrary;
use App\Domains\Admin\Services\Service;
use App\Domains\Admin\Services\Sale\OrderService;
use App\Domains\Admin\Services\Member\MemberService;
use App\Repositories\Eloquent\Sale\MaterialRequestionRepository;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class MaterialRequisitionService extends Service
{
    private $lang;

	public function __construct(public MaterialRequestionRepository $repository
    , protected OrderService $OrderService
    , protected MemberService $MemberService
    )
	{
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/common/common','admin/sale/mrequisition',]);
	}


    public function getRequisitions($queries)
    {
        $queries['filter_required_date'] = '2023-04-01-2023-04-30';

        //送達日 $delivery_date
        if(!empty($queries['filter_required_date'])){
            $rawSql = $this->repository->parseDateToSqlWhere('required_date', $queries['filter_required_date']);
            if($rawSql){
                $queries['WhereRawSqls'][] = $rawSql;
            }
            unset($queries['filter_required_date']);
        }


        $requisitions = $this->repository->getRows($queries);

        return $requisitions;

        //echo '<pre>', print_r($requisitions, 1), "</pre>"; exit;
        //$this->calcRequisitions($queries);

        /*
        $mrequisitions[] = [
            'id' => 1,
            'required_date' => '2023-05-01',
        ];

        $mrequisitions[] = [
            'id' => 2,
            'required_date' => '2023-05-02',
        ];

        $mrequisitions[] = [
            'id' => 3,
            'required_date' => '2023-05-03',
        ];
        $collection = new Collection($mrequisitions);

        $page = 2;
        $perPage = 10;
        $total = $collection->count();

        //$results = $collection->forPage($page, $perPage)->get();
        $results = Collection::make($mrequisitions);

        $paginator = new LengthAwarePaginator($results, $total, $perPage, $page);
        echo '<pre>', print_r($paginator, 1), "</pre>"; exit;

        return $paginator;
        */

    }


    /*
    public function calcRequisitions($queries)
    {
        echo '<pre>', print_r(999, 1), "</pre>"; exit;
        

        if(empty($queries['required_date'])){
            return false;
        }

        $filter_date = [
            //'filter_delivery_date' => $queries['required_date'],
            //'regexp' => false,
            'WhereRawSqls' => ["delivery_date LIKE '".$queries['required_date']."%'"],
            'pagination' => false,
        ];
        $orders = $this->OrderService->getRows($filter_date);

        //parseDate()
        echo '<pre>orders ', print_r($orders->toArray(), 1), "</pre>"; exit;

        $queries['required_date']= [
            '2023-04-01',
            '2023-04-30'
        ];

        if(!empty($queries['required_date'])){
            if(is_array($queries['required_date'])){
                $date1 = $queries['required_date'][0];
                $date2 = $queries['required_date'][1];
            }
            echo '<pre>', print_r(999, 1), "</pre>"; exit;
            echo '<pre>', print_r($date1, 1), "</pre>"; exit;
        }

        $filter_data = [
            'delivery_date' => 11,

        ];
        $this->OrderService->getOrders($filter_data);



    }
    */

    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {
            if(empty($data['required_date'])){
                return false;
            }
    
            // delete
            $this->repository->newModel()->where('required_date', $data['required_date'])->delete();
    
            $now = Carbon::now('utc')->toDateTimeString();
    
            foreach ($data['material_product'] as $material_product) {
                $mrequisitions[] = [
                    'required_date' => $data['required_date'],
                    'product_id' => $material_product['product_id'],
                    'quantity' => $material_product['quantity'],
                    'created_at'=> $now,
                    'updated_at'=> $now
                ];
            }
    
            if($this->repository->newModel()->insert($mrequisitions)){
                $result = [
                    'success' => true,
                    'required_date' => $data['required_date'],
                ];
            }

            DB::commit();
            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            $msg = $ex->getMessage();
            $result['error'] = $msg;
            return $result;
        }




       

        // $mrequisitions = $this->repository->getRows($data);



        // echo '<pre>mrequisitions ', print_r($mrequisitions, 1), "</pre>"; exit;

    }


}