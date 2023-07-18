<?php

namespace App\Domains\Admin\ViewComposers;

use Illuminate\View\View;
use App\Libraries\TranslationLibrary;

class WrapperComposer
{
    private $lang;
    private $auth_user;
    private $acting_user;
    private $base;
    
    /**
     * Create a new sidebar composer.
     *
     * @param  ...
     * @return void
     */
    //public function __construct(UserRepository $users)
    public function __construct()
    {
        $this->auth_user = auth()->user();
        $this->acting_user = auth()->user();
        $this->base = config('app.admin_url');

        // Translations
        $groups = [
            'admin/common/common',
            'admin/common/column_left',
            'admin/setting/setting',
        ];
        $this->lang = (new TranslationLibrary())->getTranslations($groups);
    }

    /**
     * Bind data to the view.
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view)
    {
        $view->with('auth_user', $this->auth_user);
        $view->with('acting_user', $this->acting_user);
        $view->with('base', $this->base);
        $view->with('appName', env('APP_NAME'));
        $view->with('location_id', 1);

    }
}
