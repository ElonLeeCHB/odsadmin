<?php

namespace App\Domains\Admin\Services\Localization;

use App\Services\Service;
use App\Repositories\Eloquent\Localization\LanguageRepository;

class LanguageService extends Service
{
    protected $modelName = "\App\Models\Localization\Language";

	public function __construct(public LanguageRepository $repository)
	{
	}
}