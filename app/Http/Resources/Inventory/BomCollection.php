<?php
// 未使用
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class BomCollection extends ResourceCollection
{
    public function toStandardObjects()
    {
        return $this->collection->map(function ($row) {
            return (object) [
                'id' => $row->id,
                'name' => $row->name,
                // 添加其他屬性
            ];
        });
    }

    public function toArray($request)
    {
        return [
            'data' => $this->toStandardObjects(),
        ];
    }
}