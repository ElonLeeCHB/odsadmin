<?php

/**
 * This class is used to replace laraval's original localization, and is used in all controllers.
 * Created by Ron, 2022
 */

namespace App\Libraries;

use App\Models\Localization\Translation;
use Lang;

class TranslationLibrary
{
    public $fallback_locale;
    public $locale;
    public $group;
    public $driver;
    public $data;

    // getTransByGroups
    public function getTranslations($groups)
    {        
        $this->groups = $groups;
        $this->driver = config('app.translatoin_driver');

        $fallback_locale = config('app.fallback_locale');
        $locale = \App::getLocale();

        if($this->driver == 'file') {
            $translations = $this->getFileTranslations($locale);
            if($this->fallback_locale !== $this->locale){
                $fallback_translations = $this->getFileTranslations($fallback_locale);
                $translations = array_replace_recursive($fallback_translations, $translations);
            }
        }
        else if($this->driver == 'database') {
            $translations = $this->getDatabaseTranslations($locale);
            if($this->fallback_locale !== $this->locale){
                $fallback_translations = $this->getDatabaseTranslations($fallback_locale);
                
                $translations = array_replace_recursive($fallback_translations, $translations);
            }
        }

        return $translations;
    }

    public function getDatabaseTranslations($locale)
    {
        $this->data = new TranslationData();

        foreach ($this->groups as $group) {
            $query = Translation::query();

            $rows = $query->select('key','value')
                ->where('locale',$locale)
                ->where('group',$group)
                ->get()->pluck('value','key')->toArray();

            foreach ($rows as $key => $value) {
                $this->data->$key = $value;
            }
        }
        return $this->data;
    }

    public function getFileTranslations($locale)
    {
        $this->data = new TranslationData();

        foreach ($this->groups as $group) {
            $arr = Lang::get($group);
            if(is_array($arr)){
                foreach (Lang::get($group) as $key => $value) {
                    $this->data->$key = $value;
                }
            }
        }
        
        return $this->data;
    }
}

class TranslationData
{
    public function __get($key)
    {
        if(isset($this->$key)){
            return $this->$key;
        }
        return $key;     
    }

    public function trans($key)
    {
        return $this->__get($key);
    }
}