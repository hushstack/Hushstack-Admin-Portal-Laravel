<?php

namespace App\Jobs;

use App\Mail\AccountDeletedSuccessMail;
use App\Models\AccountDeletion;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class DeleteAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $userId) {}

    public function handle(): void
    {
        $deletion = AccountDeletion::where('user_id', $this->userId)->first();
        if (!$deletion) return;

        if (now()->lt($deletion->execute_at)) return;

        $user = User::find($this->userId);
        if (!$user) {
            $deletion->delete();
            return;
        }

        $email = $user->email;

        $user->tokens()->delete();
        $user->delete();

        $deletion->delete();

        Mail::to($email)->queue(new AccountDeletedSuccessMail());
    }
}
