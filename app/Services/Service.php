<?php

namespace App\Services;

use Exception;

class Service
{
    //public $locale = 'zh_Hant';
    private $repository;

    public function resetRecord($record, $quries)
    {
        $record = $record->toArray();

        if(isset($record['translation'])){
            unset($record['translation']);
        }

        if(!empty($quries['columns'])){
            foreach ($quries['columns'] as $key => $column) {
                if(isset($record[$column])){
                    $result[$column] = $record[$column];
                }
            }
        }else{
            $result = $record;
        }

        return (object)$record;
    }

    public function unsetTranslation($record)
    {
        if(!empty($record->translation)){
            $record->unsetRelation('translation');
        }

        return $record;
    }

    public function find($id)
    {
        $record = $this->repository->find($id);

        return $this->unsetTranslation($record);
    }

    public function findOrNew($data)
    {
        $record = $this->repository->findOrNew($data);

        return $this->unsetTranslation($record);
    }

    public function first($data)
    {
        $record = $this->repository->first($data);

        return $this->unsetTranslation($record);
    }

    public function firstOrNew($data)
    {
        $record = $this->repository->firstOrNew($data);

        return $this->unsetTranslation($record);
    }


	public function getRow($data, $debug=0)
	{
        $record = $this->repository->getRow($data, $debug);

        return $this->unsetTranslation($record);
	}

	public function getRows($data=[], $debug=0)
	{
        return $this->repository->getRows($data, $debug);
	}

    public function getNewModelInstance()
    {
        return $this->repository->newModel();
    }


    public function generatorRows($query) {
        foreach ($query->cursor() as $rows) {
            yield $rows;
        }
    }


    //如果 model 有搭配套件 Astrotomic/laravel-translatable
    // public function saveTranslationData($model, $data, $translatedAttributes=null, $debug=0)
    // {
    //     if(empty($translatedAttributes)){
    //         $translatedAttributes = $this->repository->model->translatedAttributes;
    //     }

    //     foreach($data as $locale => $value){
    //         foreach ($translatedAttributes as $column) {
    //             if(!empty($value[$column])){
    //                 $model->translateOrNew($locale)->$column = $value[$column];
    //             }
    //         }
    //     }

    //     return $model->save();
    // }

}
