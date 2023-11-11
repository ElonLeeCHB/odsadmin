<?php

namespace App\Observers;

use App\Models\Catalog\ProductUnit;

class ProductUnitObserver
{
    public function saving(ProductUnit $row)
    {
        if(empty($row->factor)){
            $row->factor = $row->destination_quantity / $row->source_quantity;
        }
    }

    public function creating(ProductUnit $row)
    {
        if(empty($row->factor)){
            $row->factor = $row->destination_quantity / $row->source_quantity;
        }
    }
}

?>