<?php

namespace App\Services\Contact;

use App\Jobs\Contact\SendContactJob;
use App\Models\UserRequest;

class ContactService
{
    public function send(array $payload): void
    {
        // You can add metadata if you want
        $submittedAt = now();
        $payload['submitted_at'] = $submittedAt->toDateTimeString();
        $payload['app_name'] = config('app.name');
        $payload['app_url'] = config('app.url');

        UserRequest::create([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'message' => $payload['message'],
            'type_req' => 'contact',
            'ip_address' => $payload['ip_address'] ?? null,
            'app_name' => $payload['app_name'],
            'app_url' => $payload['app_url'],
            'submitted_at' => $submittedAt,
        ]);

        SendContactJob::dispatch($payload);
    }
}
