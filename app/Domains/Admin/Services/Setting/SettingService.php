<?php

namespace App\Domains\Admin\Services\Setting;

use App\Services\Service;
use App\Repositories\Eloquent\Setting\SettingRepository;
use Illuminate\Support\Facades\DB;

class SettingService extends Service
{
    protected $modelName = "\App\Models\Setting\Setting";
	public $repository;

	public function __construct(private SettingRepository $SettingRepository)
	{}

	public function getSetting($data)
	{
        if(!empty($data['id'])){
            $setting = $this->SettingRepository->newModel()->find($data['id']);
        }else{
			$queries = [
				'filter_orgination_id' => $data['orgination_id'],
				'filter_group' => $data['group'],
				'filter_key' => $data['key'],
			];
            $setting = $this->SettingRepository->getRow($queries); // not yet
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

		$this->SettingRepository->upsert($data, $whereColumns, $updateColumns);
	}


	public function updateOrCreate($data)
	{
        DB::beginTransaction();

        try {
            $setting = $this->SettingRepository->findIdOrFailOrNew($data['setting_id']);
			
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
}