<?php

namespace App\Domains\Admin\Services\Common;

use App\Domains\Admin\Services\Service;
use Illuminate\Support\Facades\DB;

class TaxonomyService extends Service
{
    protected $modelName = "\App\Models\Common\Taxonomy";

    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {
            $taxonomy = $this->findIdOrFailOrNew($data['taxonomy_id']);
            $taxonomy->code = $data['code'] ?? '';
            $taxonomy->is_active = $data['is_active'] ?? '';

            $taxonomy->save();

            if(!empty($data['taxonomy_translations'])){
                $this->saveTranslationData($taxonomy, $data['taxonomy_translations']);
            }

            DB::commit();
           
            return ['taxonomy_id' => $taxonomy->id];

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }
}
