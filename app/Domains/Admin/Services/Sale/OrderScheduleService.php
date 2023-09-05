<?php

namespace App\Domains\Admin\Services\Sale;

use App\Models\Sale\Order;

class OrderScheduleService extends Service
{
    protected $modelName = "\App\Models\Sale\Order";
    private $lang;

    public function __construct(public OrderRepository $repository
        , private OrderProductRepository $OrderProductRepository
        , private OrderTotalRepository $OrderTotalRepository
        , private OptionService $OptionService
        , private MemberRepository $MemberRepository
        , private TermRepository $TermRepository
    )
    {
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/sale/order',]);
    }

}