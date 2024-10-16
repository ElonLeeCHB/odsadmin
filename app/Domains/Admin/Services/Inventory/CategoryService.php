<?php

namespace App\Domains\Admin\Services\Inventory;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Inventory\CategoryRepository;

class CategoryService extends Service
{
    protected $modelName = "\App\Models\Common\Term";

    public function __construct(protected CategoryRepository $CategoryRepository)
    {
        $this->repository = $CategoryRepository;
    }
}
