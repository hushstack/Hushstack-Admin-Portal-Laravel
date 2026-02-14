<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public int $maxAttempts = 5;
    public int $resendCooldownSeconds = 60;

    public function send(User $user, int $minutes = 10): void
    {
        DB::transaction(function () use ($user, $minutes) {
            // Only 1 active OTP per user (because no purpose)
            Otp::where('user_id', $user->id)
                ->whereNull('used_at')
                ->delete();

            $code = (string) random_int(100000, 999999);

            Otp::create([
                'user_id' => $user->id,
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes($minutes),
                'last_sent_at' => now(),
                'attempts' => 0,
            ]);

            Mail::to($user->email)->queue(new OtpMail($code, $minutes));
        });
    }

    public function resend(User $user, int $minutes = 10): bool
    {
        $otp = Otp::where('user_id', $user->id)
            ->whereNull('used_at')
            ->latest()
            ->first();

        if ($otp && $otp->last_sent_at && now()->diffInSeconds($otp->last_sent_at) < $this->resendCooldownSeconds) {
            return false;
        }

        $this->send($user, $minutes);
        return true;
    }

    public function verify(User $user, string $code): bool
    {
        $otp = Otp::where('user_id', $user->id)
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (!$otp) return false;
        if (now()->gte($otp->expires_at)) return false;
        if ($otp->attempts >= $this->maxAttempts) return false;

        $otp->increment('attempts');

        if (!Hash::check($code, $otp->code_hash)) {
            return false;
        }

        $otp->update(['used_at' => now()]);
        return true;
    }
}
