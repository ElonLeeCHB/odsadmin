<?php

namespace App\Listeners;

use App\Events\OrderSaved;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Sale\OrderProduct;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductTranslation;
use App\Helpers\Classes\DataHelper;
use App\Models\Sale\Order;
use Carbon\Carbon;

use App\Events\OrderSavedAfterCommit;

class SendToAdminEmail
{
    use InteractsWithQueue;

    public function handle($event)
    {


    }
}