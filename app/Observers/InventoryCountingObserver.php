<?php

namespace App\Observers;

use App\Models\Inventory\Counting;

class InventoryCountingObserver
{
	public function saving(Counting $row)
	{
		if(empty($row->code)){
			$row->code = $this->getCode();
		}
	}

	public function creating(Counting $row)
    {
		if(empty($row->code)){
			$row->code = $this->getCode();
		}
	}

	public function getCode()
	{
		$code_prefix = date('Y') . sprintf("%02d",date('m'));

		$max_code = Counting::where('code', 'like', $code_prefix.'%')->max('code');
		$new_sn = empty($max_code) ? 1 : substr($max_code,6)+1 ;
		$new_code = $code_prefix . sprintf("%03d",$new_sn) ;
		
		return $new_code;
	}
}

?>