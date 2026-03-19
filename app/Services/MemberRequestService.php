<?php

namespace App\Services;

use App\Jobs\Member\SendMemberRequestJob;
use App\Models\UserRequest;

class MemberRequestService
{
    public function send(array $payload): void
    {
        $submittedAt = now();
        $payload['submitted_at'] = $submittedAt->toDateTimeString();
        $payload['app_name'] = config('app.name');
        $payload['app_url'] = config('app.url');

        UserRequest::create([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'message' => $payload['message'],
            'type_req' => 'member',
            'telegram_number' => $payload['telegram_number'],
            'ip_address' => $payload['ip_address'] ?? null,
            'app_name' => $payload['app_name'],
            'app_url' => $payload['app_url'],
            'submitted_at' => $submittedAt,
        ]);

        SendMemberRequestJob::dispatch($payload);
    }
}
