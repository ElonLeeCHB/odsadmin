<?php

namespace App\Domains\Admin\Services;

use Exception;
use App\Traits\Model\EloquentTrait;
use Illuminate\Database\Eloquent\Model;

class ServiceV2
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

    public function findId($id, $data)
    {
        return $this->tFind($id, $data);
    }

    public function findOrFailOrNew($id=null, $data=[])
    {
        //find
        if(!empty($id)){
            $record = $this->tFind($id, $data);

            if(empty($record)){ //fail
                $error = 'Record not found!';
                throw new Exception($error);
            }
        }

        //new
        else{
            $record = $this->newModel();
        }

        return $record;
    }

    public function first($data): Model
    {
        return $this->tFirst($data);
    }

    public function findOrNew($data): Model
    {
        return $this->tFindOrNew($data);
    }

    public function firstOrNew($data): Model
    {
        return $this->tFirstOrNew($data);
    }

	public function getRow($data, $debug=0): Model
	{
        return $this->getModelInstance($data, $debug);
	}

	public function getRows($data=[], $debug=0)
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
