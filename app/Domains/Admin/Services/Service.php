<?php

namespace App\Domains\Admin\Services;

class Service
{
    //public $locale = 'zh_Hant';


    public function newModel()
    {
        return $this->repository->newModel();
    }


    public function findId($id)
    {
        return $this->repository->findId($id);
    }


    public function findIdOrNew($id)
    {
        return $this->repository->findIdOrNew($id);
    }
    
    
    public function findIdOrFailOrNew($id)
    {
        return $this->repository->findIdOrFailOrNew($id);
    }


    public function first($data)
    {
        return $this->repository->first($data);
    }


    public function firstOrNew($data)
    {
        return $this->repository->firstOrNew($data);
    }


	public function getRow($data, $debug=0)
	{
        return $this->repository->getRow($data, $debug);
	}


    public function getRows($data=[], $debug=0)
	{
        return $this->repository->getRows($data, $debug);
	}


    public function toStdObj($modelInstance)
    {
        $arr = $modelInstance->toArray();

        if(!empty($arr['translation'])){
            unset($arr['translation']);
        }

        return (object)$arr;

    }

    /**
     * 獲取 meta_data，並根據 meta_keys，若 meta_key 不存在，設為空值 ''
     */
    public function setMetaDataset($row)
    {
        $indexed_meta_dataset = [];
        $meta_keys = $row->meta_keys;
        $meta_dataset = $row->meta_dataset;

        foreach ($meta_dataset as $meta_data) {
            $indexed_meta_dataset[$meta_data->meta_key] = $meta_data->meta_value;
            $existed_meta_keys[] = $meta_data->meta_key;
        }

        foreach ($meta_keys as $meta_key) {
            if(empty($indexed_meta_dataset[$meta_key] )){
                $indexed_meta_dataset[$meta_key] = '';
            }
        }

        return (object)$indexed_meta_dataset;
    }

    public function saveMetaDataset($masterModel, $data)
    {
        $metaDataset  = [
            ['meta_key' => 'supplier_contact_name', 'meta_value' => $data['meta_data_supplier_contact_name']],
            ['meta_key' => 'supplier_contact_email', 'meta_value' => $data['meta_data_supplier_contact_email']],
            ['meta_key' => 'supplier_contact_jobtitle', 'meta_value' => $data['meta_data_supplier_contact_jobtitle']],
            ['meta_key' => 'supplier_contact_telephone', 'meta_value' => $data['meta_data_supplier_contact_telephone']],
            ['meta_key' => 'supplier_contact_mobile', 'meta_value' => $data['meta_data_supplier_contact_mobile']],
        ];
        foreach ($metaDataset as $row) {
            $masterModel->meta_dataset()->updateOrCreate(['meta_key' => $row['meta_key']], $row);
        }
    }
    
}
