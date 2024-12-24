<?php

namespace App\Domains\Admin\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Models\Sale\Test;
use App\Models\Sale\OrderProduct;
use App\Models\Sale\OrderProductOption;
use App\Models\Catalog\Product;

class TestController extends Controller
{
    public function __construct(private Request $request)
    {}


    public function index()
    {
        echo '<pre>', print_r('TestController', 1), "</pre>"; exit;
    }

}
