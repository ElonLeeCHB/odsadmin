<?php

namespace App\Domains\Admin\Services\Counterparty;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Services\Counterparty\PaymentTermService as GlobalPaymentTermService;
use App\Repositories\Eloquent\Counterparty\PaymentTermRepository;

class PaymentTermService extends GlobalPaymentTermService
{
    protected $modelName = "\App\Models\Counterparty\PaymentTerm";

	public function __construct(protected PaymentTermRepository $PaymentTermRepository)
	{}


    /**
     * 
     */
    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {

            // 儲存主記錄
            $payment_term = $this->PaymentTermRepository->findIdOrFailOrNew($data['payment_term_id']);

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
