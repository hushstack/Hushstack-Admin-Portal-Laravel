<?php

namespace App\Http\Controllers\Api\NoteFlow;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\NoteFlow\UpdateAppearanceSettingsRequest;
use App\Http\Requests\NoteFlow\UpdateNotificationSettingsRequest;
use App\Http\Requests\NoteFlow\UpdateProfileSettingsRequest;
use App\Http\Resources\NoteFlow\SettingsResource;
use App\Services\AuthService;
use App\Services\NoteFlow\SettingsService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuthService $auth
    ) {}

    public function show(Request $request)
    {
        return $this->successResponse(new SettingsResource([
            'user' => $request->user(),
            'settings' => $this->settings->getOrCreate($request->user()),
        ]), 'Settings loaded.');
    }

    public function updateProfile(UpdateProfileSettingsRequest $request)
    {
        $user = $request->user();
        $user->update($request->validated());

        return $this->successResponse(new SettingsResource([
            'user' => $user->fresh(),
            'settings' => $this->settings->getOrCreate($user),
        ]), 'Profile settings updated.');
    }

    public function updateAppearance(UpdateAppearanceSettingsRequest $request)
    {
        $settings = $this->settings->updateAppearance($request->user(), $request->validated());

        return $this->successResponse($settings->appearance, 'Appearance settings updated.');
    }

    public function updateNotifications(UpdateNotificationSettingsRequest $request)
    {
        $settings = $this->settings->updateNotifications($request->user(), $request->validated());

        return $this->successResponse([
            'email_notifications' => $settings->email_notifications,
            'push_notifications' => $settings->push_notifications,
        ], 'Notification settings updated.');
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $data = $request->validated();
        $changed = $this->auth->changePassword(
            $request->user(),
            $data['current_password'],
            $data['new_password']
        );

        if (! $changed) {
            return $this->validationErrorResponse(['current_password' => ['Current password incorrect.']]);
        }

        return $this->successResponse(null, 'Password changed.');
    }

    public function sessions(Request $request)
    {
        $currentTokenId = $request->user()->currentAccessToken()?->id;

        $sessions = $request->user()->tokens()
            ->latest()
            ->get()
            ->map(fn ($token) => [
                'id' => $token->id,
                'device' => $token->name,
                'last_active_at' => optional($token->last_used_at)->toISOString(),
                'expires_at' => optional($token->expires_at)->toISOString(),
                'current' => $token->id === $currentTokenId,
            ]);

        return $this->successResponse($sessions, 'Sessions loaded.');
    }

    public function revokeSession(Request $request, int $id)
    {
        $deleted = $request->user()->tokens()->whereKey($id)->delete();

        if ($deleted < 1) {
            return $this->notFoundResponse('Session');
        }

        return $this->successResponse(null, 'Session revoked.');
    }

    public function enableTwoFactor()
    {
        return $this->successResponse([
            'enabled' => false,
            'message' => 'Two-factor authentication is not configured yet.',
        ], 'Two-factor setup status loaded.');
    }
}
