<?php

namespace App\Repositories\Eloquent\Sale;

use App\Domains\Admin\Traits\Eloquent;

class OrderProductIngredientRepository
{
    use Eloquent;

    public $modelName = "\App\Models\Sale\OrderProductIngredient";
}

