<?php

namespace App\Observers;

use App\Models\Sale\Order;

class OrderObserver
{
	public function saving(Order $order)
	{
		if(empty($order->code)){
			$order->code = $this->getOrderCode();
		}

		if(empty($order->salutation_code)){
			$order->salutation_code = 0;
		}
	}

	public function creating(Order $order)
    {
		if(empty($order->code)){
			$order->code = $this->getOrderCode();
		}
	}

	public function getOrderCode()
	{
		$code_prefix = substr(date('Y'),2,2) . sprintf("%02d",date('m'));
		$max_code = Order::where('code', 'like', $code_prefix.'%')->max('code');
		$new_sn = empty($max_code) ? 1 : substr($max_code,4)+1 ;
		$new_code = $code_prefix . sprintf("%04d",$new_sn) ;
		
		return $new_code;
	}
}

?>