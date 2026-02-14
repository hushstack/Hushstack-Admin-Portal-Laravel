<?php

namespace App\Services;

use App\Jobs\DeleteAccountJob;
use App\Mail\AccountDeleteWarningMail;
use App\Models\AccountDeletion;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class AccountDeletionService
{
    public function request(User $user): void
    {
        $executeAt = now()->addMinute();

        AccountDeletion::updateOrCreate(
            ['user_id' => $user->id],
            ['execute_at' => $executeAt]
        );

        Mail::to($user->email)->queue(new AccountDeleteWarningMail());

        DeleteAccountJob::dispatch($user->id)->delay($executeAt);
    }
}
