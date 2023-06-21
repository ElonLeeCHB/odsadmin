<?php

namespace App\Domains\Admin\Services\Common;

use App\Domains\Admin\Services\Service;
use App\Repositories\Eloquent\Common\TaxonomyRepository;
use DB;

class TaxonomyService extends Service
{
    public $repository;

	public function __construct(TaxonomyRepository $repository)
	{
        $this->repository = $repository;
	}


    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {
            $taxonomy = $this->repository->findIdOrFailOrNew($data['taxonomy_id']);
            $taxonomy->code = $data['code'] ?? '';
            $taxonomy->is_active = $data['is_active'] ?? '';

            $taxonomy->save();

            if(!empty($data['taxonomy_translations'])){
                $this->repository->saveTranslationData($taxonomy, $data['taxonomy_translations']);
            }

            DB::commit();
           
            return ['taxonomy_id' => $taxonomy->id];

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }
}
