<?php

namespace App\Helpers\Classes;

use App\Helpers\Classes\OrmHelper;

use Illuminate\Support\Facades\DB;
use App\Models\Catalog\ProductUnit;

/**
 * 2023-11-14
 * Ron Lee
 */
class UnitConverter
{
    protected float $fromQty = 0;
    protected $fromUnit;
    protected $toUnit;

    protected $standard_unit_codes;

    // unit conversion
    protected $uc_table_name = 'units';
    protected $uc_column_source_unit_code = 'code';
    protected $uc_column_source_quantity = 'factor';
    protected $uc_column_destination_unit_code = 'base_unit_code';

    // product unit conversion
    protected $product_id = '';
    protected $puc_table_name = 'product_units';
    protected $puc_column_product_id = 'product_id';
    protected $puc_column_source_unit_code = 'source_unit_code';
    protected $puc_column_source_quantity = 'source_quantity';
    protected $puc_column_destination_unit_code = 'destination_unit_code'; // This is stock_unit_code, generally wont' allow change.
    protected $puc_column_destination_quantity = 'destination_quantity';

    // 私有構造函數，防止直接實例化
    private function __construct() {}

    public static function build()
    {
        return new self();
    }

    public function qty($fromQty)
    {
        $this->fromQty = (float) $fromQty;
        return $this;
    }

    public function from($unit)
    {
        $this->fromUnit = $unit;
        return $this;
    }

    public function to($unit)
    {
        $this->toUnit = $unit;
        return $this;
    }

    public function product($product_id)
    {
        $this->product_id = $product_id;
        return $this;
    }

    public function get()
    {
        $standard_unit_codes = $this->getStandardUnitCodes();

        $qty = 0;
        $toQty = 0;

        // 單位相同，不需換算直接回傳
        if ($this->fromUnit == $this->toUnit) {
            return $this->fromQty;
        }
        // 來源跟目的都是公制單位
        else if (in_array($this->fromUnit, $standard_unit_codes) && in_array($this->toUnit, $standard_unit_codes)) {
            $from = DB::table($this->uc_table_name)
                ->where($this->uc_column_source_unit_code, $this->fromUnit)
                ->where('is_standard', 1)
                ->first();

            $to = DB::table($this->uc_table_name)
                ->where($this->uc_column_source_unit_code, $this->toUnit)
                ->where('is_standard', 1)
                ->first();

            // 基準單位必須相同
            if ($from->base_unit_code !== $to->base_unit_code) {
                return ['error' => '單位的基準單位必須相同！來源單位：' . $this->fromUnit . ', 基準單位：' . $from->base_unit_code . ', 目的單位：' . $this->toUnit . ', 基準單位：' . $to->base_unit_code];
            }

            $toQty = (float) $this->fromQty * (float) $from->factor / (float) $to->factor;

        }
        // 來源或目的其中一個是非公制單位。依附於產品的單位換算
        else if (!empty($this->product_id)) {
            $productUnit = DB::table($this->puc_table_name)
                ->where($this->puc_column_product_id, $this->product_id)
                ->where($this->puc_column_source_unit_code, $this->fromUnit)
                ->where($this->puc_column_destination_unit_code, $this->toUnit)
                ->first();

            // 如果沒有找到，反向再找一次。例如原本要找 1包=幾公斤？如果沒找到，則用公斤找包，也許有。
            if (empty($productUnit->destination_unit_code)) {
                $query = ProductUnit::query();
                $query->where('product_id', $this->product_id);
                $query->where('source_unit_code', $this->toUnit);
                $query->where('destination_unit_code', $this->fromUnit);
                OrmHelper::prepare($query);

                $reverseProductUnit = $query->first();

                if (!empty($reverseProductUnit)) {
                    $productUnit = (object) [
                        'product_id' => $reverseProductUnit->product_id,
                        'source_unit_code' => $reverseProductUnit->destination_unit_code,
                        'source_quantity' => $reverseProductUnit->destination_quantity,
                        'destination_unit_code' => $reverseProductUnit->source_unit_code,
                        'destination_quantity' => $reverseProductUnit->source_quantity,
                        'factor' => $reverseProductUnit->source_quantity / $reverseProductUnit->destination_quantity,
                    ];
                }
            }

            if (!empty($productUnit)) {
                // echo '<pre>$productUnit->factor = ', print_r($productUnit->factor, true), "</pre>";
                // echo gettype($productUnit->factor);
                // echo gettype($this->fromQty);

                $toQty = (float) $this->fromQty * (float) $productUnit->factor;
            }
        }

        return $toQty ?? 0;
    }

    private function getStandardUnitCodes()
    {
        if(empty($this->standard_unit_codes)){
            $this->standard_unit_codes = DB::table($this->uc_table_name)
                        ->where('is_standard', 1)
                        ->pluck('code')
                        ->toArray();
        }

        return $this->standard_unit_codes;
    }
}
