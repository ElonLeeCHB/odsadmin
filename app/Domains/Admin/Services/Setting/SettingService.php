<?php

namespace App\Domains\Admin\Services\Setting;

use App\Domains\Admin\Services\Service;
use App\Repositories\Eloquent\Setting\SettingRepository;
use Illuminate\Support\Facades\DB;

class SettingService extends Service
{
    protected $modelName = "\App\Models\Setting\Setting";
	public $repository;

	public function __construct(SettingRepository $SettingRepository)
	{
        $this->repository = $SettingRepository;
	}

	public function getSetting($data)
	{
        if(!empty($data['id'])){
            $setting = $this->repository->newModel()->find($data['id']);
        }else{
			$queries = [
				'filter_orgination_id' => $data['orgination_id'],
				'filter_group' => $data['group'],
				'filter_key' => $data['key'],
			];
            $setting = $this->repository->getRow($queries); // not yet
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

		$this->repository->upsert($allData, $whereColumns, $updateColumns);
	}


	public function updateOrCreate($data)
	{
        DB::beginTransaction();

        try {
            $setting = $this->repository->findIdOrFailOrNew($data['setting_id']);

			$setting->location_id = $data['location_id'] ?? 0;
			$setting->group = $data['group'];
			$setting->setting_key = $data['setting_key'];
			$setting->setting_value = $data['setting_value'];
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