<?php

namespace App\Domains\Admin\Services\Setting;

use App\Services\Service;
use App\Repositories\Eloquent\Setting\SettingRepository;
use Illuminate\Support\Facades\DB;

class SettingService extends Service
{
    protected $modelName = "\App\Models\Setting\Setting";
	public $repository;

	public function __construct(private SettingRepository $SettingRepository)
	{
		$this->repository = $SettingRepository;
	}
}