<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Member\StoreMemberRequest;
use App\Http\Requests\Admin\Member\UpdateMemberRequest;
use App\Http\Resources\MemberResource;
use App\Models\Member;
use App\Services\MemberService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private readonly MemberService $memberService) {}

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $members = $this->memberService->getAll($perPage);

        return $this->successResponse(MemberResource::collection($members), 'Members loaded.');
    }

    public function show(Member $member)
    {
        $member->load(['user', 'position']);

        return $this->successResponse(new MemberResource($member), 'Member detail loaded.');
    }

    public function store(StoreMemberRequest $request)
    {
        $member = $this->memberService->create($request->validated());
        $member->load(['user', 'position']);

        return $this->successResponse(new MemberResource($member), 'Member created.', 201);
    }

    public function update(UpdateMemberRequest $request, Member $member)
    {
        $member = $this->memberService->update($member, $request->validated());
        $member->load(['user', 'position']);

        return $this->successResponse(new MemberResource($member), 'Member updated.');
    }

    public function destroy(Member $member)
    {
        $this->memberService->delete($member);

        return $this->successResponse(null, 'Member deleted.');
    }
}
