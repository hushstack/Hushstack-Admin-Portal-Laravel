<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\Messenger\MessengerApiException;
use App\Http\Controllers\Controller;
use App\Services\Messenger\MessengerApiClient;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class MessengerUserController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private readonly MessengerApiClient $messenger) {}

    public function index(Request $request)
    {
        $filters = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'string', 'max:255'],
        ]);

        try {
            $users = $this->messenger->users($filters);
        } catch (MessengerApiException $exception) {
            return $this->errorResponse(
                $exception->getMessage(),
                $exception->statusCode(),
                null,
                'MESSENGER_API_ERROR'
            );
        }

        // Passed through untouched so the UI keeps the Messenger pagination envelope.
        return response()->json($users);
    }
}
