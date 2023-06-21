<?php

namespace App\Repositories\Eloquent\Catalog;

use App\Repositories\Eloquent\Repository;

class CategoryRepository extends Repository
{
    public $modelName = "\App\Models\Catalog\Category";

    public $translationModel = "\App\Models\Common\TermTranslation";
}

