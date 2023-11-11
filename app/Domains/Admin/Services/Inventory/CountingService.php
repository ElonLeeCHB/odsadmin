<?php

namespace App\Domains\Admin\Services\Inventory;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Inventory\CountingRepository;

class CountingService extends Service
{
    protected $modelName = "\App\Models\Inventory\Counting";

    public function __construct(private CountingRepository $CountingRepository)
    {
        $this->repository = $CountingRepository;
    }

}