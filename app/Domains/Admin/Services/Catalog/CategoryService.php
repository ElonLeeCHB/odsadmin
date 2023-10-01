<?php

namespace App\Domains\Admin\Services\Catalog;

//use App\Services\Service;
use App\Services\Catalog\CategoryService as GlobalCategoryService;

class CategoryService extends GlobalCategoryService
{
    protected $modelName = "\App\Models\Common\Term";
}