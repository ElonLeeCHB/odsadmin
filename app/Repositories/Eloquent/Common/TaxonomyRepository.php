<?php

namespace App\Repositories\Eloquent\Common;

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

    public function resetQueryData($data)
    {
        return $data;
    }
}

