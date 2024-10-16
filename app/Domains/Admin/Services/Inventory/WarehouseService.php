<?php

namespace App\Domains\Admin\Services\Inventory;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Inventory\WarehouseRepository;

class WarehouseService extends Service
{
    protected $modelName = "\App\Models\Inventory\Warehouse";

	public function __construct(private WarehouseRepository $WarehouseRepository)
	{
        $this->repository = $WarehouseRepository;
    }
}