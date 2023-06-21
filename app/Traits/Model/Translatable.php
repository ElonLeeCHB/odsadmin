<?php

namespace App\Traits\Model;

/**
 * 在 Repository.php 判斷 model 若有 translatedAttributes，則 令 model實例使用 with('translation')。
 * 然後該實例的 model 檔 必須 use 本類別，就會存在 $modelInstance->translation 關聯可供呼叫。
 */

trait Translatable
{
    public function getLocaleKey(): string
    {
        return $this->localeKey ?: config('translatable.locale_key', 'locale');
    }

    public function translations($translationModelName = null)
    {
        if(empty($translationModelName)){
            $translationModelName = get_class($this) . 'Translation';
            $translactionModel = new $translationModelName();
        }

        return $this->hasMany($translactionModel::class);
    }

    public function translation($locale = null, $translationModelName = null)
    {
        if(empty($locale)){
            $locale = \App::getLocale();
        }

        if(empty($translationModelName)){
            $translationModelName = get_class($this) . 'Translation';
            $translactionModel = new $translationModelName();
        }

        return $this->hasOne($translactionModel::class)->ofMany([
            'id' => 'max',
        ], function ($query) {
            $query->where('locale', app()->getLocale());
        });
        
    }

    
    /**
     * @param  string|array|null  $locales  The locales to be deleted
     */
    public function deleteTranslations($locales = null): void
    {
        if ($locales === null) {
            $translations = $this->translations()->get();
        } else {
            $locales = (array) $locales;
            $translations = $this->translations()->whereIn($this->getLocaleKey(), $locales)->get();
        }

        $translations->each->delete();

        // we need to manually "reload" the collection built from the relationship
        // otherwise $this->translations()->get() would NOT be the same as $this->translations
        $this->load('translations');
    }

    public function metaData($metaModelName = null)
    {
        if(empty($metaModelName)){
            $metaModelName = get_class($this) . 'Meta';
            $instance = new $metaModelName();
        }

        return $this->hasMany($instance::class);
    }



}