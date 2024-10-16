<?php

namespace App\Domains\Admin\Services\Counterparty;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Counterparty\PaymentTermRepository;

class PaymentTermService extends Service
{
    protected $modelName = "\App\Models\Counterparty\PaymentTerm";

	public function __construct(protected PaymentTermRepository $PaymentTermRepository)
	{
        $this->repository = $PaymentTermRepository;
    }


    /**
     * 
     */
    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {

            // 儲存主記錄
            $result = $this->PaymentTermRepository->findIdOrFailOrNew($data['payment_term_id']);

            if(empty($result['error']) && !empty($result['data'])){
                $payment_term = $result['data'];
            }else{
                return response(json_encode($result))->header('Content-Type','application/json');
            }

            $payment_term->type = $data['type'] ?? 1;
            $payment_term->name = $data['name'];
            $payment_term->comment = $data['comment'];
            $payment_term->due_date_basis = $data['due_date_basis'] ?? 2;
            $payment_term->due_date_plus_months = $data['due_date_plus_months'] ?? 0;
            $payment_term->due_date_plus_days = $data['due_date_plus_days'] ?? 0;
            $payment_term->is_active = $data['is_active'] ?? 0;
            $payment_term->sort_order = $data['sort_order'] ?? 100;
            $payment_term->save();

            DB::commit();

            $result['payment_term_id'] = $payment_term->id;
            return $result;
            
        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];

        }
        
        return false;
    }
}
