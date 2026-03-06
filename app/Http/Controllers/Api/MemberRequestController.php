<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\SendMemberRequest;
use App\Services\MemberRequestService;

class MemberRequestController extends Controller
{
    public function __construct(private readonly MemberRequestService $memberRequestService)
    {
    }

    public function store(SendMemberRequest $request)
    {
        $payload = $request->validated();
        $payload['ip_address'] = $request->ip();

        $this->memberRequestService->send($payload);

        return response()->json([
            'success' => true,
            'message' => 'Member request submitted.',
        ], 201);
    }
}
