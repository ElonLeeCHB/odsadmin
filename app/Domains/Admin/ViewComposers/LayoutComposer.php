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
            $Catalog[] = [
                'name'	   => $this->lang->text_tag,
                'icon'	   => '',
                'href'     => 'javascript:void()',
                'children' => []
            ];
        }

        if(1) {
            $Catalog[] = [
                'name'	   => $this->lang->text_category,
                'icon'	   => '',
                'href'     => route('lang.admin.catalog.categories.index'),
                'children' => []
            ];
        }
        */

        if(1) {
            $Catalog[] = [
                'name'	   => $this->lang->text_catalog_category,
                'icon'	   => '',
                'href'     => route('lang.admin.catalog.categories.index', ['equal_is_active' => 1]),
                'children' => []
            ];
        }

        if(1) {
            $Catalog[] = [
                'name'	   => $this->lang->text_catalog_tag,
                'icon'	   => '',
                'href'     => route('lang.admin.catalog.tags.index', ['equal_is_active' => 1]),
                'children' => []
            ];
        }

        if(1) {
            $Catalog[] = [
                'name'	   => $this->lang->text_option,
                'icon'	   => '',
                'href'     => route('lang.admin.catalog.options.index', ['equal_is_active' => 1]),
                'children' => []
            ];
        }

        if(1) {
            $Catalog[] = [
                'name'	   => $this->lang->text_product,
                'icon'	   => '',
                'href'     => route('lang.admin.catalog.products.index', ['equal_is_active' => 1]),
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
            $Catalog[] = [
                'name'	   => $this->lang->text_attribute,
                'icon'	   => ' ',
                'children' => $attributesParent
            ];
        }

        if(!empty($Catalog) && ($this->acting_user->username == 'admin')) {
            $menus[] = [
                'id'       => 'menu-system',
                'icon'	   => 'fas fa-cog',
                'name'	   => $this->lang->text_catalog,
                'href'     => '',
                'children' => $Catalog
            ];
        }


        // Sales
        $sale = [];

        if(1) {
            $sale[] = [
                'name'	   => $this->lang->text_order,
                'icon'	   => '',
                'href'     => route('lang.admin.sale.orders.index'),
                'children' => []
            ];
        }

        if(1) {
            $sale[] = [
                'name'	   => $this->lang->text_individual,
                'icon'	   => '',
                'href'     => route('lang.admin.member.members.index'),
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

        //訂單排程
        if(1) {
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
                'href'     => route('lang.admin.sale.requisitions.index'),
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
                'href'     => route('lang.admin.common.financial_institutions.index'),
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
                'href'     => route('lang.admin.inventory.receiving.index'),
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
                'name'	   => $this->lang->text_menu_inventory,
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

        // 門市設定
        if(1) {
            $system[] = [
                'name'	   => '門市設定',
                'icon'	   => '',
                'href'     => route('lang.admin.setting.locations.index'),
                'children' => []
            ];
        }
        
        // System User
        $user = [];

        // users
        if (1) {
            $user[] = [
                'name'	   => '使用者',
                'href'     => route('lang.admin.setting.user.users.index', ['equal_is_admin' => 1, 'equal_is_active' => 1]),
                'icon'	   => ' ',
            ];
        }

        // permissions
        if (1) {
            $user[] = [
                'name'	   => '權限',
                'href'     => route('lang.admin.setting.user.permissions.index', ['equal_is_active' => 1]),
                'icon'	   => ' ',
            ];
        }

        // roles
        if (1) {
            $user[] = [
                'name'	   => '角色',
                'href'     => '/',
                'icon'	   => ' ',
            ];
        }

        if ($user) {
            $system[] = [
                'name'	   => '使用者',
                'icon'	   => ' ',
                'children' => $user
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
