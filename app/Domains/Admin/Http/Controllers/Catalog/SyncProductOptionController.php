<?php

namespace App\Domains\Admin\Http\Controllers\Catalog;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;

class SyncProductOptionController extends BackendController
{
    public function __construct(private Request $request)
    {
        parent::__construct();

        $this->getLang(['admin/common/common', 'admin/common/term', 'admin/catalog/tag']);
    }

    /**
     * 顯示同步商品選項的表單。
     */
    public function create()
    {
        $products = Product::with('options')->get();

        return view('admin.sync-options.create', compact('products'));
    }

    /**
     * 處理同步商品選項。
     */
    public function store(Request $request)
    {
        $request->validate([
            'source_product_id' => 'required|exists:products,id',
            'option_ids'        => 'required|array',
            'option_ids.*'      => 'exists:options,id',
            'target_product_ids' => 'required|array',
            'target_product_ids.*' => 'exists:products,id',
        ]);

        $sourceProductId = $request->input('source_product_id');
        $optionIds = $request->input('option_ids');
        $targetProductIds = $request->input('target_product_ids');

        DB::transaction(function () use ($optionIds, $targetProductIds) {
            foreach ($targetProductIds as $productId) {
                $product = Product::find($productId);
                if ($product) {
                    // 假設 options 是 Many-to-Many 關聯
                    $product->options()->syncWithoutDetaching($optionIds);
                }
            }
        });

        return redirect()
            ->back()
            ->with('success', '商品選項已成功同步至目標商品。');
    }
}
