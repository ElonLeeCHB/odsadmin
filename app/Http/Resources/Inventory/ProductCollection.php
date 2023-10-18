<?php
// 暫不使用
namespace App\Http\Resources\Inventory;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollection extends ResourceCollection
{
    public function toStdObjects()
    {
        return $this->collection->map(function ($row) {
            return (object) [
                'id' => $row->id,
                'name' => 333,
            ];
        });
    }

    public function toArray($request)
    {
        // return [
        //     'data' => $this->toStandardObjects(),
        // ];
    }
}