<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\SendContactRequest;
use App\Services\Contact\ContactService;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function __construct(private readonly ContactService $contactService)
    {
    }

    public function send(SendContactRequest $request)
    {
        $payload = $request->validated();
        $payload['ip_address'] = $request->ip();

        $this->contactService->send($payload);

        return response()->json([
            'success' => true,
            'message' => 'Your message has been sent successfully.',
        ]);
    }
}
