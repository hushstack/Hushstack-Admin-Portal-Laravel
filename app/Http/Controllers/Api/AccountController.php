<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AccountDeletionService;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(private AccountDeletionService $accountDeletion) {}

    public function requestDelete(Request $request)
    {
        $this->accountDeletion->request($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Delete requested. Warning sent to email. Account will delete after 1 minute.',
        ]);
    }
}
