<?php

namespace App\Domains\Api\Services;

use Exception;
use App\Traits\Model\EloquentTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class Service
{
    use EloquentTrait{
        find as protected tFind;
        first as protected tFirst;
        findOrNew as protected tFindOrNew;
        firstOrNew as protected tFirstOrNew;
    }

    public $modelName;
    public $model;
    public $table;

    //public $locale = 'zh_Hant';

	public function __construct()
	{
        $this->model = new $this->modelName;
	}

    public function find($id, $data=[]) // return Model or Collection
    {
        return $this->tFind($id, $data);
    }

    public function findOrNew($id, $data=[])
    {
        return $this->tFindOrNew($id, $data);
    }

    public function findIdOrFailOrNew($id, $data=[])
    {
        $query = $this->newModel()->query();

        if(!empty($data['columns'])){
            $query->select($data['columns']);
        }

        if(!empty($data['with'])){
            $query->with($data['with']);
        }

        if(!empty($data['appends'])){
            $query->appends($data['appends']);
        }

        //find
        if(!empty($id)){
            $instance = $this->newModel()->find($id);
            
            if(empty($instance)){ //fail
                $error = 'Record not found!';
                throw new Exception($error);
            }
        }
        //new
        else{
            $instance = $this->newModel();
        }

        return $instance;
    }

    public function first($data): Model
    {
        return $this->tFirst($data);
    }


    public function firstOrNew($data): Model
    {
        return $this->tFirstOrNew($data);
    }

	public function getRecord($data, $debug=0): Model|null
	{
        return $this->getModelInstance($data, $debug);
	}

	public function getRecords($data=[], $debug=0)
	{
        return $this->getModelCollection($data, $debug);
	}

    public function removeRecordsTranslation($recordsArray)
    {
        foreach($recordsArray as $key => $array){
            if($key != 'translation'){
                $result[$key] = $array;
            }
        }
        return $result;
    }


    public function generatorRows($query)
    {
        foreach ($query->cursor() as $rows) {
            yield $rows;
        }
    }
}
