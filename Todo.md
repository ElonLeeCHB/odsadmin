待處理 要套用新做法：app/Cache

SupplierCollection 只是格式。要存成快取。

D:\Codes\PHP\DTSCorp\Chinabing\ods\htdocs\laravel\app\Domains\Admin\Services\Counterparty\SupplierService.php

            // 常用廠商
            if(isset($data['is_often_used_supplier']) && $data['is_often_used_supplier']){

                $params = [
                    //'select' => ['id', 'name', 'short_name', 'tax_rate', 'formatted_tax_rate'],
                    'with' => 'metas',
                    'whereHas' => ['metas' => ['meta_key' => 'is_often_used_supplier', 'meta_value' => 1]],
                    'pagination' => false,
                    'limit' => 0
                ];
                $suppliers = $this->getRows($params);

                foreach ($suppliers as $key => $supplier) {
                    $this->getMetaRows($supplier);
                }

                $suppliers_resource_collection = (new SupplierCollection($suppliers))->toArray();
                
                $cache_name = 'cache/counterparty/suppliers/often_used.json';
                DataHelper::setJsonToStorage($cache_name, $suppliers_resource_collection);
            }

