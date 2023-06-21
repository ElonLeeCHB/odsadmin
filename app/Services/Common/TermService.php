<?php

namespace App\Services\Common;

use App\Libraries\TranslationLibrary;
use App\Services\Service;
use App\Repositories\Eloquent\Common\TermRepository;
use DB;

class TermService extends Service
{
	public function __construct(public TermRepository $repository)
	{
        $groups = [
            'admin/common/common',
            'admin/common/term',
        ];
        $this->lang = (new TranslationLibrary())->getTranslations($groups);
	}
}
