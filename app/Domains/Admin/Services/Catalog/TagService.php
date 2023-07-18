<?php

namespace App\Domains\Admin\Services\Catalog;

use Illuminate\Support\Facades\DB;
use App\Domains\Admin\Services\Service;
use App\Models\Common\Term;
use App\Models\Common\TermTranslation;
use App\Models\Common\TermRelation;

class TagService extends Service
{
    protected $modelName = "\App\Models\Common\Term";

	public function getTags($data=[], $debug = 0)
	{
        $data['equal_taxonomy_code'] = 'product_tag';

        $categories = $this->getRows($data, $debug);

        return $categories;
	}


    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {
            $term = $this->findIdOrFailOrNew($data['tag_id']);

            $term->parent_id = $data['parent_id'] ?? 0;
            $term->code = $data['code'] ?? '';
            $term->slug = $data['slug'] ?? '';
            $term->taxonomy_code = 'product_tag';
            $term->comment = $data['comment'] ?? '';
            $term->sort_order = $data['sort_order'] ?? 999;
            $term->is_active = $data['is_active'] ?? 0;

            $term->save();

            if(!empty($data['translations'])){
                $this->saveTranslationData($term, $data['translations']);
            }

            DB::commit();

            $result['tag_id'] = $term->id;
            
            return $result;
        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }


    public function deleteTag($tag_id)
    {
        try {

            DB::beginTransaction();

            TermTranslation::where('term_id', $tag_id)->delete();
            TermRelation::where('term_id', $tag_id)->delete();
            Term::where('id', $tag_id)->delete();

            DB::commit();

            $result['success'] = true;

            return $result;
            
        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }



}