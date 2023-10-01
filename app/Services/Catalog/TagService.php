<?php

namespace App\Services\Catalog;

use App\Services\Service;
use App\Repositories\Eloquent\Catalog\TagRepository;

class TagService extends Service
{
    protected $modelName = "\App\Models\Common\Term";

    public function __construct(protected TagRepository $TagRepository)
    {}

    public function getTags($data=[], $debug = 0)
    {
        return $this->TagRepository->getTags($data, $debug);
    }


    public function deleteTagById($tag_id)
    {
        return $this->TagRepository->deleteTagById($tag_id);
    }


    public function updateOrCreateTag($data)
    {
        $data['taxonomy_code'] = 'product_tag';
        $data['term_id'] = $data['tag_id'];
        
        return $this->TagRepository->updateOrCreateTag($data);
    }


}