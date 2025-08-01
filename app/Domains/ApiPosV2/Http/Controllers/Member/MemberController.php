<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Member;

use Illuminate\Http\Request;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use App\Domains\ApiPosV2\Services\Member\MemberService;
use App\Rules\ValidPassword;
use Illuminate\Support\Facades\Hash;

class MemberController extends ApiPosController
{
    public function __construct(private Request $request, private MemberService $service)
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }
    }

    public function list()
    {
        $members = $this->service->getList(request()->all());

        return response()->json($members, 200, [], JSON_UNESCAPED_UNICODE); 
    }

    public function info($member_id = null)
    {
        $member = $this->service->getInfo($member_id, request()->all());

        if (empty($member)){
            return $this->sendJsonResponse(['error' => true], 404);
        }

        return $this->sendJsonResponse($member);
    }
}