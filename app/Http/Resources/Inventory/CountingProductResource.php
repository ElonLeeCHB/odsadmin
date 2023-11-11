<?php
 
namespace App\Http\Resources\Inventory;
 
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
 
class CountingProductResource extends JsonResource
{
    public function toArray(Request $request = null): array
    {
        if(is_numeric($this->stock_quantity) && is_numeric($this->quantity)){
            $factor = $this->stock_quantity / $this->quantity;
        }else{
            $factor = 1;
        }
        
        return [
            'id' => $this->id,
            'counting_id' => $this->counting_id,
            'product_id' => $this->product_id,
            'product_name' => $this->product->translation->name ?? '',
            'product_specification' => $this->product->translation->specification ?? '',
            'quantity' => $this->quantity,
            'stock_unit_code' => $this->product->stock_unit_code ?? '',
            'stock_unit_name' => $this->product->stock_unit->name ?? '',
            'unit_code' => $this->unit_code,
            'unit_name' => $this->unit->name ?? '',
            'price' => $this->price,
            'amount' => $this->amount,
            'comment' => $this->comment,
            'stock_quantity' => $this->stock_quantity,
            'factor' => $factor,
        ];
    }

    public function toStdClass()
    {
        return (object) $this->toArray();
    }

}