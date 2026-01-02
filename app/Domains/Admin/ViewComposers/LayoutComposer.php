<?php

namespace App\Domains\Admin\ViewComposers;

use Illuminate\View\View;
use App\Libraries\TranslationLibrary;
use Illuminate\Support\Facades\Lang;

class LayoutComposer
{
    private $lang;
    private $auth_user;
    private $acting_user;
    private $base;

    /**
     * Create a new sidebar composer.
     *
     * @param  ...
     * @return void
     */
    //public function __construct(UserRepository $users)
    public function __construct()
    {
        $this->auth_user = auth()->user();
        $this->acting_user = auth()->user();
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/common/common','admin/common/column_left','admin/setting/setting']);
    }

    /**
     * Bind data to the view.
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view)
    {
        $view->with('auth_user', $this->auth_user);
        $view->with('acting_user', $this->acting_user);
        $view->with('navigation', $this->lang->text_navigation);

        $leftMenus = $this->getColumnLeft();
        $view->with('menus', $leftMenus);

    }

    public function getColumnLeft()
    {
        $menus[] = [
            'id'       => 'menu-dashboard',
            'icon'	   => 'fas fa-home',
            'name'	   => $this->lang->text_dashboard,
            'href'     => route('lang.admin.dashboard'),
            'children' => []
        ];

        /**
         * Catalog
         */
        /*
        if(1) {
            $catalog[] = [
                'name'	   => $this->lang->text_tag,
                'icon'	   => '',
                'href'     => 'javascript:void()',
                'children' => []
            ];
        }

        if(1) {
            $catalog[] = [
                'name'	   => $this->lang->text_category,
                'icon'	   => '',
                'href'     => route('lang.admin.catalog.categories.index'),
                'children' => []
            ];
        }
        */

        // if(1) {
        //     $catalog[] = [
        //         'name'	   => 'POS商品分類',
        //         'icon'	   => '',
        //         'href'     => route('lang.admin.catalog.poscategories.index', ['equal_is_active' => 1]),
        //         'children' => []
        //     ];
        // }

        // if(1) {
        //     $catalog[] = [
        //         'name'	   => '訂單列印分類',
        //         'icon'	   => '',
        //         'href'     => route('lang.admin.catalog.order-printing-categories.index', ['equal_is_active' => 1]),
        //         'children' => []
        //     ];
        // }

        if(1) {
            $catalog[] = [
                'name'	   => $this->lang->text_option,
                'icon'	   => '',
                'href'     => route('lang.admin.catalog.options.index', ['equal_is_active' => 1]),
                'children' => []
            ];
        }

        if(1) {
            $catalog[] = [
                'name'	   => $this->lang->text_product,
                'icon'	   => '',
                'href'     => route('lang.admin.catalog.products.index', ['equal_is_active' => 1]),
                'children' => []
            ];
        }

        if (1) {
            $catalog[] = [
                'name'       => '同步商品選項',
                'icon'       => '',
                'href'       => route('lang.admin.catalog.syncProductOptions.index'),
                'children' => []
            ];
        }

        // 成本計算
        if (1) {
            $catalog[] = [
                'name'       => $this->lang->text_cost_estimation,
                'icon'       => '',
                'href'     => route('lang.admin.catalog.cost_estimations.index'),
                'children' => []
            ];
        }

        //
        $level_2 = [];

        // L3
        if (0) {
            $attributesParent[] = [
                'name'	   => $this->lang->text_attribute,
                'href'     => '/',
                'icon'	   => ' ',
            ];
        }

        // L3
        if (0) {
            $attributesParent[] = [
                'name'	   => $this->lang->text_attribute_group,
                'href'     => '/',
                'icon'	   => ' ',
            ];
        }

        if (!empty($attributesParent)) {
            $catalog[] = [
                'name'	   => $this->lang->text_attribute,
                'icon'	   => ' ',
                'children' => $attributesParent
            ];
        }

        if(!empty($catalog) && ($this->acting_user->username == 'admin')) {
            $menus[] = [
                'id'       => 'menu-system',
                'icon'	   => 'fas fa-cog',
                'name'	   => $this->lang->text_catalog,
                'href'     => '',
                'children' => $catalog
            ];
        }


        // Sales
            $sale = [];

            if(1) {
                $sale[] = [
                    'name'	   => $this->lang->text_order,
                    'icon'	   => '',
                    'href'     => route('lang.admin.sale.orders.index'),
                    // 'href'     => env('APP_URL') . '/#/ordered',
                    'children' => []
                ];
            }

            if(1) {
                $sale[] = [
                    'name'	   => $this->lang->text_individual,
                    'icon'	   => '',
                    'href'     => route('lang.admin.member.members.index'),
                    'href'     => env('APP_URL') . '/#/member',
                    'children' => []
                ];
            }

            if(1) {
                $sale[] = [
                    'name'	   => $this->lang->text_sales_phrase,
                    'icon'	   => '',
                    'href'     => route('lang.admin.sale.phrases.index'),
                    'children' => []
                ];
            }

            // //優惠券類別
            // if (1) {
            //     $sale[] = [
            //         'name'       => '優惠券類別',
            //         'icon'       => '',
            //         'href'     => route('lang.admin.sale.coupon_types.index'),
            //         'children' => []
            //     ];
            // }

            //優惠券記錄
            if (1) {
                $sale[] = [
                    'name'       => '優惠券記錄',
                    'icon'       => '',
                    'href'     => '',
                    'children' => []
                ];
            }

            // //優惠券
            // if (1) {
            //     $sale[] = [
            //         'name'       => $this->lang->text_sales_order_schedule,
            //         'icon'       => '',
            //         'href'     => route('lang.admin.sale.order_schedule.index'),
            //         'children' => []
            //     ];
            // }

            //訂單排程
            if(0) {
                $sale[] = [
                    'name'	   => $this->lang->text_sales_order_schedule,
                    'icon'	   => '',
                    'href'     => route('lang.admin.sale.order_schedule.index'),
                    'children' => []
                ];
            }

            //備料單
            if(1) {
                $sale[] = [
                    'name'	   => $this->lang->text_menu_sale_requisition,
                    'icon'	   => '',
                    'href'     => route('lang.admin.sale.requisitions.form'),
                    'children' => []
                ];
            }

            //備料單設定
            if($this->acting_user->username == 'admin') {
                $sale[] = [
                    'name'	   => $this->lang->text_material_requisition_setting,
                    'icon'	   => '',
                    'href'     => route('lang.admin.sale.requisitions.setting'),
                    'children' => []
                ];
            }

            // 上暉料件需求
            // if(1) {
            //     $inventory[] = [
            //         'name'	   => '上暉料件需求',
            //         'icon'	   => '',
            //         'href'     => route('lang.admin.inventory.shRequirements.index'),
            //         'children' => []
            //     ];
            // }


            // add to Menus
            if(!empty($sale)) {
                $menus[] = [
                    'id'       => 'menu-sale',
                    'icon'	   => 'fas fa-user',
                    'name'	   => $this->lang->text_menu_sale,
                    'href'     => '',
                    'children' => $sale
                ];
            }
        // Sales End


        // Reports
            $report = [];

            if (1) {
                $report[] = [
                    'name'       => '營運月報表',
                    'icon'       => '',
                    'href'     => route('lang.admin.reports.operation-monthly.index'),
                    'children' => []
                ];
            }

            if (1) {
                $report[] = [
                    'name'       => '年度訂單分析',
                    'icon'       => '',
                    'href'     => route('lang.admin.reports.annual-order.index'),
                    'children' => []
                ];
            }

            // add to Menus
            if (!empty($report)) {
                $menus[] = [
                    'id'       => 'menu-reports',
                    'icon'       => 'fas fa-chart-bar',
                    'name'       => '報表系統',
                    'href'     => '',
                    'children' => $report
                ];
            }
        // Reports End


        // Common
        $common = [];

        if($this->acting_user->username == 'admin') {
            $common[] = [
                'name'	   => $this->lang->text_common_taxonomy,
                'icon'	   => '',
                'href'     => route('lang.admin.common.taxonomies.index'),
                'children' => []
            ];
        }

        if($this->acting_user->username == 'admin') {
            $common[] = [
                'name'	   => $this->lang->text_common_term,
                'icon'	   => '',
                'href'     => route('lang.admin.common.terms.index'),
                'children' => []
            ];
        }

        // 付款條件
        if(1) {
            $common[] = [
                'name'	   => $this->lang->text_common_payment_term,
                'icon'	   => '',
                'href'     => route('lang.admin.common.payment_terms.index'),
                'children' => []
            ];
        }

        //金融機構
        if(1) {
            $common[] = [
                'name'	   => $this->lang->text_financial_institution,
                'icon'	   => '',
                'href'     => route('lang.admin.counterparty.banks.index'),
                'children' => []
            ];
        }

        // add to Menus
        if(!empty($common)) {
            $menus[] = [
                'id'       => 'menu-common',
                'icon'	   => 'fas fa-user',
                'name'	   => $this->lang->text_common,
                'href'     => '',
                'children' => $common
            ];
        }
        // Common End


        // Inventory Management 庫存管理
        $inventory = [];

        if(1) {
            $inventory[] = [
                'name'	   => $this->lang->text_inventory_supplier,
                'icon'	   => '',
                'href'     => route('lang.admin.counterparty.suppliers.index'),
                'children' => []
            ];
        }

        if(1) {
            $inventory[] = [
                'name'	   => $this->lang->text_inventory_warehouse,
                'icon'	   => '',
                'href'     => route('lang.admin.inventory.warehouses.index'),
                'children' => []
            ];
        }

        if(1) {
            $inventory[] = [
                'name'	   => $this->lang->text_unit,
                'icon'	   => '',
                'href'     => route('lang.admin.inventory.units.index'),
                'children' => []
            ];
        }

        // 料件分類建立作業
        if(1) {
            $inventory[] = [
                'name'	   => $this->lang->text_inventory_category,
                'icon'	   => '',
                'href'     => route('lang.admin.inventory.categories.index'),
                'children' => []
            ];
        }

        // 料件建立作業
        if(1) {
            $inventory[] = [
                'name'	   => $this->lang->text_inventory_products,
                'icon'	   => '',
                'href'     => route('lang.admin.inventory.products.index'),
                'children' => []
            ];
        }

        // BOM表
        if(1) {
            $inventory[] = [
                'name'	   => $this->lang->text_inventory_bom,
                'icon'	   => '',
                'href'     => route('lang.admin.inventory.boms.index'),
                'children' => []
            ];
        }

        // 採購作業
        // if(1) {
        //     $inventory[] = [
        //         'name'	   => $this->lang->text_purchasing_orders,
        //         'icon'	   => '',
        //         'href'     => route('lang.admin.inventory.purchasing.index'),
        //         'children' => []
        //     ];
        // }

        // 進貨作業
        if(1) {
            $inventory[] = [
                'name'	   => $this->lang->text_receiving_orders,
                'icon'	   => '',
                'href'     => route('lang.admin.inventory.receivings.index'),
                'children' => []
            ];
        }

        // 盤點作業
        if(1) {
            $inventory[] = [
                'name'	   => $this->lang->text_counting_task,
                'icon'	   => '',
                'href'     => route('lang.admin.inventory.countings.index'),
                'children' => []
            ];
        }
        if(1) {
            $inventory[] = [
                'name'	   => '盤點料件設定',
                'icon'	   => '',
                'href'     => route('lang.admin.inventory.countings.productSettings'),
                'children' => []
            ];
        }

        // 訂單料件表
        if(1) {
            $inventory[] = [
                'name'	   => '料件需求表',
                'icon'	   => '',
                'href'     => route('lang.admin.inventory.materialRequirements.index'),
                'children' => []
            ];
        }

        // add to Menus
        if(!empty($inventory)) {
            $menus[] = [
                'id'       => 'menu-inventory',
                'icon'	   => 'fas fa-user',
                'name'	   => $this->lang->text_inventory,
                'href'     => '',
                'children' => $inventory
            ];
        }
        // Inventory End


        // Members
        $member = [];

        if(0) {
            $member[] = [
                'name'	   => $this->lang->text_individual,
                'icon'	   => '',
                'href'     => route('lang.admin.member.members.index'),
                'children' => []
            ];
        }

        if(0) {
            $member[] = [
                'name'	   => $this->lang->text_organization,
                'icon'	   => '',
                'href'     => route('lang.admin.member.organizations.index'),
                'children' => []
            ];
        }

        // add to Menus
        if(!empty($member)) {
            $menus[] = [
                'id'       => 'menu-members',
                'icon'	   => 'fas fa-user',
                'name'	   => $this->lang->text_member,
                'href'     => '',
                'children' => $member
            ];
        }

        /**
         * System
         */
        // 參數設定
        if(1) {
            $system[] = [
                'name'	   => '參數設定',
                'icon'	   => '',
                'href'     => route('lang.admin.setting.settings.index'),
                'children' => []
            ];
        }

        // 門市管理
        if(1) {
            $system[] = [
                'name'	   => '門市管理',
                'icon'	   => '',
                'href'     => route('lang.admin.system.stores.index'),
                'children' => []
            ];
        }

        // 訪問控制 (Access Control)
        $access = [];

        // users
        if (1) {
            $access[] = [
                'name'	   => '使用者',
                'href'     => route('lang.admin.system.access.users.index', ['equal_is_active' => 1]),
                'icon'	   => ' ',
            ];
        }

        // permissions
        if (1) {
            $access[] = [
                'name'	   => '權限',
                'href'     => route('lang.admin.system.access.permissions.index'),
                'icon'	   => ' ',
            ];
        }

        // roles
        if (1) {
            $access[] = [
                'name'	   => '角色',
                'href'     => route('lang.admin.system.access.roles.index'),
                'icon'	   => ' ',
            ];
        }

        // menus
        if (1) {
            $access[] = [
                'name'	   => '選單',
                'href'     => route('lang.admin.system.access.menus.index'),
                'icon'	   => ' ',
            ];
        }

        if ($access) {
            $system[] = [
                'name'	   => '訪問控制',
                'icon'	   => ' ',
                'children' => $access
            ];
        }

        // 系統日誌
        if (1) {
            $logs = [];

            $logs[] = [
                'name'     => '資料庫',
                'href'     => route('lang.admin.system.logs.index'),
                'icon'     => ' ',
            ];

            $logs[] = [
                'name'     => '歷史壓縮檔',
                'href'     => route('lang.admin.system.logs.archived.index'),
                'icon'     => ' ',
            ];

            $system[] = [
                'name'     => '系統日誌',
                'icon'     => '',
                'children' => $logs
            ];
        }


        // Localisation
        $localisation = [];

        // Localisation Languages
        if (1) {
            $localisation[] = [
                'name'	   => 'Languages',
                'href'     => '/Languages',
                'icon'	   => ' ',
            ];
        }

        // Localisation Translations
        if (1) {
            $localisation[] = [
                'name'	   => 'Translations',
                'href'     => '/Translations',
                'icon'	   => ' ',
            ];
        }

        //if ($localisation) {
        if (0) {
            $system[] = [
                'name'	   => 'Localization',
                'icon'	   => ' ',
                'children' => $localisation
            ];
        }

        // System Maintenance
        $maintenance = [];

        // System Maintenance Tools
        if (1) {
            $tools[] = [
                'name'	   => 'Trans From OC',
                'href'     => route('lang.admin.setting.maintenance.tools.trans_from_opencart'),
                'icon'	   => ' ',
                'children' => []
            ];
        }

        if (1) {
            $tools[] = [
                'name'	   => '拆解統編CSV',
                'href'     => route('lang.admin.setting.maintenance.tools.parse_uniform_invoice_number'),
                'icon'	   => ' ',
                'children' => []
            ];
        }

        if(!empty($tools)) {
            $maintenance[] = [
                'id'       => 'menu-tools',
                'icon'	   => ' ',
                'name'	   => 'Tools',
                'children' => $tools
            ];
        }


        if (0) {
            $system[] = [
                'name'	   => 'Maintenance',
                'icon'	   => ' ',
                'children' => $maintenance
            ];
        }

        if(!empty($system) && ($this->acting_user->username == 'admin')) {
            $menus[] = [
                'id'       => 'menu-setting',
                'icon'	   => 'fas fa-cog',
                'name'	   => $this->lang->text_system,
                'href'     => '',
                'children' => $system
            ];
        }

        /**
         * Example
         */
        // L2
        if(1) {
            $example[] = [
                'name'	   => 'L2 example 0',
                'icon'	   => '',
                'href'     => '/',
                'children' => []
            ];
        }
        if(1) {
            $example[] = [
                'name'	   => 'L2 example 1',
                'icon'	   => '',
                'href'     => '/',
                'children' => []
            ];
        }

        //
        $level_2 = [];

        // Localisation Languages
        if (1) {
            $level_2[] = [
                'name'	   => 'L3 example 0',
                'href'     => '/',
                'icon'	   => ' ',
            ];
        }

        // L3
        if (1) {
            $level_2[] = [
                'name'	   => 'L3 example 1',
                'href'     => '/',
                'icon'	   => ' ',
            ];
        }

        // Level3a
        if (1) {
            $level_3a[] = [
                'name'	   => 'L4 example 0',
                'href'     => '/',
                'icon'	   => ' ',
            ];
        }
        if (1) {
            $level_3a[] = [
                'name'	   => 'L4 example 1',
                'href'     => '/',
                'icon'	   => ' ',
            ];
        }

        if ($level_3a) {
            $level_2[] = [
                'name'	   => 'L3 example 2',
                'icon'	   => ' ',
                'children' => $level_3a
            ];
        }

        // level_3b
        if (1) {
            $level_3b[] = [
                'name'	   => 'L4 example 0',
                'href'     => '/',
                'icon'	   => ' ',
            ];
        }
        if (1) {
            $level_3b[] = [
                'name'	   => 'L4 example 1',
                'href'     => '/',
                'icon'	   => ' ',
            ];
        }

        if ($level_3b) {
            $level_2[] = [
                'name'	   => 'L3 example 3',
                'icon'	   => ' ',
                'children' => $level_3b
            ];
        }

        if ($level_2) {
            $example[] = [
                'name'	   => 'L2 example 2',
                'icon'	   => ' ',
                'children' => $level_2
            ];
        }

        //if(!empty($example)) {
        if(0){
            $menus[] = [
                'id'       => 'menu-system',
                'icon'	   => 'fas fa-cog',
                'name'	   => 'Example',
                'href'     => '',
                'children' => $example
            ];
        }

        return $menus;
    }
}
