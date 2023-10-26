<?php

namespace App\Repositories\Eloquent\Common;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;

class TaxonomyRepository extends Repository
{
    public $modelName = "\App\Models\Common\Taxonomy";


    public function getTaxonomies($data=[], $debug = 0)
    {
        $data = $this->resetQueryData($data);

        $taxonomies = $this->getRows($data, $debug);

        return $taxonomies;
    }

    public function saveTaxonomy($post_data, $debug = 0)
    {
        DB::beginTransaction();

        try {
            $taxonomy = $this->findIdOrFailOrNew($post_data['taxonomy_id']);

            $taxonomy->code = $post_data['code'] ?? '';
            $taxonomy->is_active = $post_data['is_active'] ?? '';

            $taxonomy->save();

            if(!empty($post_data['translations'])){
                $this->saveRowTranslationData($taxonomy, $post_data['translations']);
            }

            DB::commit();
           
            return ['taxonomy_id' => $taxonomy->id];

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }

    public function resetQueryData($data)
    {
        return $data;
    }
}

