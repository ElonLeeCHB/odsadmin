<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Member;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use App\Domains\ApiPosV2\Services\Member\MemberService;

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
        $members = $this->service->getMembers(request()->all());

        return $this->sendJsonResponse($members);
    }

    public function info($member_id = null)
    {
        $member = $this->service->getMemberById($member_id, request()->all());

        if (empty($member)){
            return $this->sendJsonResponse(['error' => true], 404);
        }

        return $this->sendJsonResponse($member);
    }

    public function store(Request $request)
    {
        try {
            return $this->service->saveMember($request);
        } catch (ValidationException $ex) {
            throw $ex;
        } catch (\Throwable $ex) {
            return $this->sendJsonErrorResponse(data: ['sys_error' => $ex->getMessage()], status_code: 500);
        }
    }

    public function update(Request $request, $member_id)
    {
        try {
            return $this->service->saveMember($request, $member_id);
        } catch (ValidationException $ex) {
            throw $ex;
        } catch (\Throwable $ex) {
            return $this->sendJsonErrorResponse(data: ['sys_error' => $ex->getMessage()], status_code: 500);
        }
    }


}