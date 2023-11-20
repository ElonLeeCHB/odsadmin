<?php

namespace App\Repositories\Eloquent\Common;

use App\Traits\EloquentTrait;
use App\Repositories\Eloquent\Common\TermRepository;

class StaticTermRepository
{
    use EloquentTrait;

    public $modelName = "\App\Models\Common\Term";
   

    public static function getCodeKeyedTermsByTaxonomyCode($taxonomy_code, $toArray = true, $params = null)
    {
        $termInstance = TermRepository::createRepository();

        return $termInstance->getCodeKeyedTermsByTaxonomyCode($taxonomy_code, $toArray, $params);
    }
}

