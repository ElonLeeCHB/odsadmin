<?php

namespace App\Domains\Api\Http\Controllers\System\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Libraries\TranslationLibrary;
use App\Domains\Api\Services\User\UserService;
use Auth;

class UserController extends Controller
{
    private $request;
    private $UserService;
    private $lang;

    public function __construct(Request $request, UserService $UserService)
    {
        $this->request = $request;
        $this->UserService = $UserService;

        // Translations
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/user/user']);
    }
}