<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\{
    RegisterRequest,
    LoginRequest,
    VerifyOtpRequest,
    ForgotPasswordRequest,
    ResetPasswordRequest,
    ChangePasswordRequest
};
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $auth,
        private OtpService $otp
    ) {}

    // REGISTER -> create user (is_verified=false) -> send OTP
    public function register(RegisterRequest $request)
    {
        $user = $this->auth->register($request->validated());

        $this->otp->send($user, 10);

        return response()->json([
            'success' => true,
            'message' => 'Registered. OTP sent to email.',
        ], 201);
    }

    // VERIFY EMAIL OTP -> set is_verified true (NO token)
    public function verifyEmailOtp(VerifyOtpRequest $request)
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        if ($user->is_verified) {
            return response()->json([
                'success' => true,
                'message' => 'Email already verified. You can login now.',
            ]);
        }

        if (!$this->otp->verify($user, $data['otp'])) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired OTP'], 422);
        }

        $user->update([
            'is_verified' => true,
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully. You can login now.',
        ]);
    }

    // LOGIN -> if verified => token immediately
    // if not verified => send OTP and block login
    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        $user = $this->auth->validateCredentials($data['email'], $data['password']);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
        }

        if (!$user->is_verified) {
            $this->otp->send($user, 10);

            return response()->json([
                'success' => false,
                'message' => 'Account not verified. OTP sent to verify email.',
            ], 403);
        }

        $user->load('role');
        $token = $this->auth->issueToken($user);

        return response()->json([
            'success' => true,
            'message' => 'Login success.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => (new UserResource($user))->toArray(request()),
        ]);
    }

    // RESEND OTP (email verification only)
    public function resendOtp(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->email)->first();

        // don’t reveal existence
        if (!$user) {
            return response()->json(['success' => true, 'message' => 'If email exists, OTP resent.']);
        }

        if ($user->is_verified) {
            return response()->json([
                'success' => true,
                'message' => 'Email already verified. No OTP needed.',
            ]);
        }

        $ok = $this->otp->resend($user, 10);

        return response()->json([
            'success' => true,
            'message' => $ok ? 'OTP resent.' : 'Please wait before resending OTP.',
        ]);
    }

    // FORGOT PASSWORD -> send OTP (no purpose, so it will replace any previous OTP)
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $email = $request->validated()['email'];

        $user = User::where('email', $email)->first();

        // do not reveal existence
        if ($user) {
            $this->otp->send($user, 10);
        }

        return response()->json([
            'success' => true,
            'message' => 'If email exists, OTP has been sent.',
        ]);
    }

    // RESET PASSWORD (one-step) -> verify OTP -> update password -> revoke tokens
    public function resetPassword(ResetPasswordRequest $request)
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        if (!$this->otp->verify($user, $data['otp'])) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired OTP'], 422);
        }

        $user->password = Hash::make($data['password']);
        $user->save();

        // revoke all tokens
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password reset success.',
        ]);
    }

    // CHANGE PASSWORD (auth)
    public function changePassword(ChangePasswordRequest $request)
    {
        $data = $request->validated();

        $user = $request->user();

        $ok = $this->auth->changePassword($user, $data['current_password'], $data['new_password']);
        if (!$ok) {
            return response()->json(['success' => false, 'message' => 'Current password incorrect'], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password changed.',
        ]);
    }

    // LOGOUT (auth)
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out.',
        ]);
    }
}
