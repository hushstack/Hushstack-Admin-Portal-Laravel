<?php

namespace App\Services\Contact;

use App\Jobs\Contact\SendContactJob;

class ContactService
{
    public function send(array $payload): void
    {
        // You can add metadata if you want
        $payload['submitted_at'] = now()->toDateTimeString();
        $payload['app_name'] = config('app.name');
        $payload['app_url']  = config('app.url');

        SendContactJob::dispatch($payload);
    }
}
