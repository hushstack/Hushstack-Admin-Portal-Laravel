<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileAddressRequest;
use App\Http\Resources\Profile\ProfileAddressResource;
use App\Http\Resources\Profile\ProfileHeaderResource;
use App\Http\Resources\Profile\ProfilePersonalInfoResource;
use App\Http\Requests\Profile\UpdateProfileHeaderRequest;
use App\Services\ProfileService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use App\Http\Requests\Profile\UpdateProfilePersonalInfoRequest;

class ProfileController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected ProfileService $profileService
    ) {}

    // GET /api/profile/header
    public function header(Request $request)
    {
        $user = $this->profileService->getProfileHeader($request->user());

        return $this->successResponse(
            new ProfileHeaderResource($user),
            'Profile header loaded.'
        );
    }

    // 🆕 PUT/PATCH /api/profile/header
    public function updateHeader(UpdateProfileHeaderRequest $request)
    {
        $user = $request->user();

        $updatedUser = $this->profileService->updateProfileHeader(
            $user,
            $request->validated()
        );

        return $this->successResponse(
            new ProfileHeaderResource($updatedUser),
            'Profile header updated.'
        );
    }

    // GET /api/profile/personal-info
    public function personalInfo(Request $request)
    {
        $user = $this->profileService->getPersonalInformation($request->user());

        return $this->successResponse(
            new ProfilePersonalInfoResource($user),
            'Personal information loaded.'
        );
    }

    // GET /api/profile/address
    public function address(Request $request)
    {
        $user = $this->profileService->getAddress($request->user());

        return $this->successResponse(
            new ProfileAddressResource($user),
            'Address information loaded.'
        );
    }

    public function updatePersonalInfo(UpdateProfilePersonalInfoRequest $request)
    {
        $user = $request->user();

        $updated = $this->profileService->updatePersonalInformation(
            $user,
            $request->validated()
        );

        return $this->successResponse(
            new ProfilePersonalInfoResource($updated),
            'Personal information updated.'
        );
    }

    public function updateAddress(UpdateProfileAddressRequest $request)
    {
        $user = $request->user();

        $updated = $this->profileService->updateAddress(
            $user,
            $request->validated()
        );

        return $this->successResponse(
            new ProfileAddressResource($updated),
            'Address information updated.'
        );
    }
}
