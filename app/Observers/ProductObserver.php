<?php

namespace App\Observers;

use App\Models\Material\Product;
use App\Helpers\Classes\DataHelper;

class ProductObserver
{
    public function deleted(Product $product)
    {
        $this->deleteProductCache($product);
    }

    /**
     * 
     */
    
    protected function deleteProductCache(Product $product)
    {
		return $product->deleteCache($product->id);
    }
}

?>