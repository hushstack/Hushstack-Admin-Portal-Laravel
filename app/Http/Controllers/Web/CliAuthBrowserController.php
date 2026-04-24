<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CliAuth\VerifyCliAuthRequest;
use App\Models\CliLoginRequest;
use App\Services\CliAuthService;
use App\Services\GoogleAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CliAuthBrowserController extends Controller
{
    public function __construct(
        private CliAuthService $cliAuthService,
        private GoogleAuthService $googleAuthService
    ) {}

    public function verify(VerifyCliAuthRequest $request)
    {
        $userCode = $request->validated()['user_code'] ?? null;

        if (! $userCode) {
            return view('cli-auth.verify');
        }

        $loginRequest = $this->cliAuthService->findByUserCode($userCode);

        if (! $loginRequest) {
            return response()->view('cli-auth.status', [
                'title' => 'Invalid code',
                'message' => 'The code you entered is invalid or no longer available.',
                'kind' => 'error',
            ], 404);
        }

        return redirect()->route('cli-auth.request.show', $loginRequest);
    }

    public function authorizeRequest(string $deviceCode)
    {
        $loginRequest = $this->cliAuthService->findByDeviceCode($deviceCode);

        if (! $loginRequest) {
            return response()->view('cli-auth.status', [
                'title' => 'Invalid device code',
                'message' => 'This CLI login link is invalid or has expired.',
                'kind' => 'error',
            ], 404);
        }

        return redirect()->route('cli-auth.request.show', $loginRequest);
    }

    public function show(CliLoginRequest $loginRequest)
    {
        if ($loginRequest->status === CliLoginRequest::STATUS_EXPIRED) {
            return response()->view('cli-auth.status', [
                'title' => 'Code expired',
                'message' => 'This CLI login request has expired. Start a new login from the CLI.',
                'kind' => 'error',
            ], 410);
        }

        if ($loginRequest->status === CliLoginRequest::STATUS_CONSUMED) {
            return view('cli-auth.status', [
                'title' => 'Already completed',
                'message' => 'This CLI login request has already been used.',
                'kind' => 'info',
            ]);
        }

        if ($loginRequest->status === CliLoginRequest::STATUS_APPROVED) {
            return view('cli-auth.status', [
                'title' => 'Approved',
                'message' => 'The CLI login has been approved. Return to the CLI to finish the exchange.',
                'kind' => 'success',
            ]);
        }

        return view('cli-auth.request', [
            'loginRequest' => $loginRequest,
            'isAuthenticated' => Auth::check(),
            'user' => Auth::user(),
        ]);
    }

    public function redirectToGoogle(CliLoginRequest $loginRequest)
    {
        if ($loginRequest->status !== CliLoginRequest::STATUS_PENDING || $loginRequest->isExpired()) {
            return redirect()->route('cli-auth.request.show', $loginRequest);
        }

        return $this->googleAuthService->redirectForCli($loginRequest);
    }

    public function googleCallback(Request $request)
    {
        $result = $this->googleAuthService->handleCliCallback($request);

        if (! $result['success']) {
            return response()->view('cli-auth.status', [
                'title' => 'Login failed',
                'message' => $result['message'] ?? 'Google login failed.',
                'kind' => 'error',
            ], $result['status'] ?? 422);
        }

        $user = $result['user'];
        $loginRequest = $this->cliAuthService->approve($result['login_request'], $user);

        Auth::guard('web')->login($user);

        return view('cli-auth.status', [
            'title' => 'CLI approved',
            'message' => 'CLI login approved successfully. Return to the terminal to finish signing in.',
            'kind' => 'success',
            'loginRequest' => $loginRequest,
        ]);
    }

    public function approve(Request $request, CliLoginRequest $loginRequest)
    {
        if (! $request->user()) {
            return redirect()->route('cli-auth.request.show', $loginRequest);
        }

        $loginRequest = $this->cliAuthService->approve($loginRequest, $request->user());

        return view('cli-auth.status', [
            'title' => 'CLI approved',
            'message' => 'CLI login approved successfully. Return to the terminal to finish signing in.',
            'kind' => 'success',
            'loginRequest' => $loginRequest,
        ]);
    }
}
