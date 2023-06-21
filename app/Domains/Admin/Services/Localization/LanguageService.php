<?php

namespace App\Domains\Admin\Services\Localization;

use App\Domains\Admin\Services\Service;
use App\Repositories\Eloquent\Localization\LanguageRepository;

class LanguageService extends Service
{
    public $modelName = "\App\Models\Localization\Language";
	private $lang;

	public function __construct(public LanguageRepository $repository)
	{
	}
}