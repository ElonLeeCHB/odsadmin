<?php

namespace App\Repositories\Eloquent\Setting;

use Illuminate\Support\Facades\DB;
use App\Traits\EloquentTrait;
use App\Models\Setting\Setting;

class SettingRepository
{
    use EloquentTrait;

    public $modelName = "\App\Models\Setting\Setting";
    

	public function getSetting($data)
	{
        if(!empty($data['id'])){
            $setting = $this->newModel()->find($data['id']);
        }else{
			$queries = [
				'filter_orgination_id' => $data['orgination_id'],
				'filter_group' => $data['group'],
				'filter_key' => $data['key'],
			];
            $setting = $this->getRow($queries); // not yet
        }

        return $setting;
	}


	public function editSettings(array $postData, array $whereColumns, $updateColumns)
	{
		foreach($postData as $key => $value) {
			if(strpos($key, 'config_') === 0){
				$data[] = [
					'location_id' => 0,
					'group' => 'config',
					'setting_key' => $key,
					'setting_value' => $value,
				];
			}
		}
		$whereColumns = ['location_id','group','setting_key'];

		$this->upsert($data, $whereColumns, $updateColumns);
	}


    public function getValueByKey($setting_key)
    {
        $result = (object)Setting::where('setting_key', $setting_key)->first()->toArray();

        return $result->setting_value;
    }
    

	public function save($data)
	{
        DB::beginTransaction();

        try {
            $result = $this->findIdOrFailOrNew($data['setting_id']);

            if(empty($result['error']) && !empty($result['data'])){
                $setting = $result['data'];
            }else{
                return response(json_encode($result))->header('Content-Type','application/json');
            }
			
			$setting->location_id = $data['location_id'] ?? 0;
			$setting->group = $data['group'];
			$setting->setting_key = $data['setting_key'];
			$setting->comment = $data['comment'] ?? null;

			if(!empty($data['is_json'])){
				$setting->setting_value = json_encode(json_decode($data['setting_value']));
			}else{
				$setting->setting_value = $data['setting_value'];
			}

			$setting->is_autoload = $data['is_autoload'];
			$setting->is_json = $data['is_json'];

			$setting->save();

            DB::commit();
    
            return ['setting_id' => $setting->id];
        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
	}


    public function destroy($ids, $debug = 0)
    {
        try {
            DB::beginTransaction();
    
            $result = Setting::whereIn('id', $ids)->delete();
            
            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }
}