<?php

namespace App\Domains\Admin\Services\Sale;

use App\Domains\Admin\Services\Service;
use App\Repositories\Eloquent\Sale\OrderRepository;
use App\Repositories\Eloquent\Sale\OrderProductRepository;
use App\Repositories\Eloquent\Sale\OrderProductOptionRepository;
use App\Repositories\Eloquent\Common\OptionRepository;

class OrderProductOptionService extends Service
{
    private $lang;

	public function __construct(
        public OrderProductOptionRepository $repository
        , public OrderProductRepository $OrderProductRepository
        , public OrderRepository $OrderRepository
        , private OptionRepository $OptionRepository

    )
	{
        //
	}


    //function get
}