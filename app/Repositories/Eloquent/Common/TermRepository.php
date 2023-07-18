<?php

namespace App\Repositories\Eloquent\Common;

use Illuminate\Support\Facades\DB;
use App\Traits\EloquentTrait;
use App\Models\Common\Term;
use App\Models\Common\TermTranslation;
use App\Models\Common\TermRelation;

class TermRepository
{
    use EloquentTrait;
    
    public $modelName = "\App\Models\Common\Term";

    public function delete($term_id)
    {
        try {

            DB::beginTransaction();

            TermRelation::where('term_id', $term_id)->delete();
            TermTranslation::where('term_id', $term_id)->delete();
            Term::where('id', $term_id)->delete();

            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }
}

