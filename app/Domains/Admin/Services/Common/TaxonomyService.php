<?php

namespace App\Domains\Admin\Services\Common;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Common\TaxonomyRepository;

class TaxonomyService extends Service
{
    protected $modelName = "\App\Models\Common\Taxonomy";

	public function __construct(protected TaxonomyRepository $TaxonomyRepository)
	{
        $this->TaxonomyRepository = $TaxonomyRepository;
	}

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

    public function deleteTaxonomy($taxonomy_id)
    {
        
    }

}
