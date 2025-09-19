<?php

namespace App\Domains\Admin\Http\Controllers\Catalog;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Repositories\Eloquent\Localization\LanguageRepository;
use App\Domains\Admin\Services\Common\TaxonomyService;
use App\Domains\Admin\Services\Inventory\CategoryService;
use App\Models\Catalog\Product;
use App\Helpers\Classes\OrmHelper;

class CostEstimationController extends BackendController
{
    protected $breadcumbs;

    public function __construct()
    {
        parent::__construct();

        $this->getLang(['admin/common/common', 'admin/common/column_left']);
    }

    protected function setBreadcumbs()
    {
        $this->breadcumbs = [];

        $this->breadcumbs[] = (object)[
            'text' => $this->lang->text_home,
            'href' => route('lang.admin.dashboard'),
        ];

        $this->breadcumbs[] = (object)[
            'text' => $this->lang->text_catalog,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $this->lang->heading_title = '成本估算';
        $this->breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.catalog.cost_estimations.index'),
        ];
    }

    public function index()
    {
        $data['lang'] = $this->lang;

        // Breadcomb
        $data['breadcumbs'] = (object)$this->breadcumbs;

        $data['list'] = $this->getList();

        $data['list_url']   = route('lang.admin.catalog.cost_estimations.list');
        $data['add_url']    = route('lang.admin.catalog.cost_estimations.form');
        $data['delete_url'] = route('lang.admin.catalog.cost_estimations.delete');

        return view('admin.catalog.cost_estimations', $data);
    }


    public function list()
    {
        return $this->getList();
    }


    private function getList()
    {
        $data['lang'] = $this->lang;

        // Prepare query_data for records
        $query_data = $this->url_data;
        $query_data['is_salable'] = 1; // 只顯示可銷售的商品

        // Rows, LengthAwarePaginator
        $query = Product::query();
        // $query->with([
        //     'printingCategory.translation:id,name',
        //     'productOptions.optionValues', // 抓選項與選項值
        //     'bom.items', // 抓 BOM 內容
        // ]);
        // $query->with(['printingCategory.translation:id,name']);
        $query->with(['printingCategory']);
        OrmHelper::prepare($query, $query_data);
        $products = OrmHelper::getResult($query, $query_data);

        if (!empty($products)) {
            foreach ($products as $row) {
                $row->edit_url = route('lang.admin.catalog.cost_estimations.form', array_merge([$row->id], $this->url_data));
                $row->printing_category_name = optional(optional($row->printingCategory)->translation)->name ?? '';
            }
            $data['products'] = $products;
            $data['pagination'] = $products->withPath(route('lang.admin.catalog.cost_estimations.list'))->appends($query_data)->links('admin.pagination.default');
        } else {
            $data['products'] = [];
            $data['pagination'] = '';
        }

        $query_data  = $this->url_data;

        // Prepare links for list table's header
        if (isset($query_data['order']) && $query_data['order'] == 'ASC') {
            $order = 'DESC';
        } else {
            $order = 'ASC';
        }

        $data['order'] = strtolower($order);

        if (isset($query_data['sort'])) {
            $data['sort'] = strtolower($query_data['sort'] ?? '');
        } else {
            $data['sort'] = '';
        }

        $query_data = $this->unsetUrlQueryData(request()->query());

        $url = '';

        foreach ($query_data as $key => $value) {
            if (is_string($value)) {
                $url .= "&$key=$value";
            }
        }

        // link of table header for sorting
        $route = route('lang.admin.catalog.products.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" . $url;
        $data['sort_name'] = $route . "?sort=name&order=$order" . $url;
        $data['sort_web_name'] = $route . "?sort=web_name&order=$order" . $url;
        $data['sort_model'] = $route . "?sort=model&order=$order" . $url;
        $data['sort_price'] = $route . "?sort=price&order=$order" . $url;
        $data['sort_quantity'] = $route . "?sort=quantity&order=$order" . $url;
        $data['sort_date_added'] = $route . "?sort=created_at&order=$order" . $url;

        return view('admin.catalog.cost_estimations_list', $data);
    }

    public function form($product_id)
    {
        $data['lang'] = $this->lang;

        // Breadcomb
        $data['breadcumbs'] = (object)$this->breadcumbs;

        $data['action'] = route('lang.admin.catalog.cost_estimations.save', $this->url_data);
        $data['cancel'] = route('lang.admin.catalog.cost_estimations.index', $this->url_data);

        $product = Product::with([
            'printingCategory.translation',
            'productOptions' => function ($q) {
                $q->with([
                    'option',
                    'productOptionValues.optionValue',
                    'productOptionValues.materialProduct',
                ]);
            },
            'bom.items' => function ($q) {
                $q->select('*'); // 可進一步限制欄位
            },
        ])->find($product_id);

        $data['product'] = $product;

        $data['back_url'] = route('lang.admin.catalog.cost_estimations.index', $this->url_data);

        return view('admin.catalog.cost_estimation_form', $data);
    }

}