<?php

namespace App\Repositories\Eloquent\Catalog;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Models\Common\Term;
use App\Models\Common\TermTranslation;
use App\Models\Common\TermRelation;

class CategoryRepository extends Repository
{
    public $modelName = "\App\Models\Catalog\Category";

    public $translationModel = "\App\Models\Common\TermTranslation";
}

