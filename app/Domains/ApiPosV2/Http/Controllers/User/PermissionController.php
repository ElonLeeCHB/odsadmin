<?php

namespace App\Domains\ApiPosV2\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Helpers\Classes\DataHelper;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use App\Domains\ApiPosV2\Services\User\PermissionService;

class PermissionController extends ApiPosController
{
    public function __construct(private Request $request, private PermissionService $PermissionService)
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }
    }

    public function list()
    {
        $filter_data = $this->url_data;

        $filter_data['select'] = ['name', 'display_name', 'guard_name'];

        $filter_data['filter_name'] = 'pos.*';

        $rows = $this->PermissionService->getList($filter_data);

        $json = [];

        $json = DataHelper::getArrayDataByPaginatorOrCollection($rows);

        $json = DataHelper::unsetArrayIndexRecursively($json, ['translation', 'translations']);

        return $this->sendResponse($json);
    }

    public function info($permission_id)
    {
        $info = $this->PermissionService->getInfo($permission_id);

        return $this->sendResponse(['data' => $info]);
    }
}
