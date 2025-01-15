<?php
/**
 * 本檔廢棄不用
 */
namespace App\Services\Sale;

use Illuminate\Support\Facades\DB;
use App\Services\Service;

use App\Repositories\Eloquent\Sale\OrderRepository;
use App\Repositories\Eloquent\Sale\OrderProductRepository;
use App\Repositories\Eloquent\Sale\OrderProductOptionRepository;
use App\Repositories\Eloquent\Sale\OrderTotalRepository;
use App\Repositories\Eloquent\Member\MemberRepository;

use App\Models\Common\Term;
use App\Models\Common\TermTranslation;
use App\Models\Common\TermRelation;
use App\Models\Sale\OrderProductOption;
use App\Models\Catalog\ProductTranslation;

use Maatwebsite\Excel\Facades\Excel;
use App\Domains\Admin\ExportsLaravelExcel\CommonExport;
use Carbon\Carbon;
use Mpdf\Mpdf;

class OrderService extends Service
{
    protected $modelName = "\App\Models\Sale\Order";
    
    public function __construct(protected OrderRepository $OrderRepository
    , protected OrderProductRepository $OrderProductRepository
    , protected OrderProductOptionRepository $OrderProductOptionRepository
        , protected OrderTotalRepository $OrderTotalRepository
        , protected MemberRepository $MemberRepository
    )
    {
        $this->repository = $OrderRepository;
    }
}