<?php
 
namespace App\Http\Resources\Inventory;
 
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
 
class CountingProductResource extends JsonResource
{
    public function toArray(Request $request = null): array
    {
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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'product_edit_url' => route('lang.admin.inventory.products.form', $this->product_id),
        ];
    }

    public function toStdClass()
    {
        return (object) $this->toArray();
    }

}