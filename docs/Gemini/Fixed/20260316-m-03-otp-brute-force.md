# Security Issue: Weak Protection Against OTP Brute Force

**ID:** 20260316-m-03-otp-brute-force
**Severity:** MEDIUM
**Date Fixed:** March 16, 2026

## Problem Description
The OTP verification logic (`OtpService::verify()`) was vulnerable to race conditions during a fast concurrent brute-force attack. While the application incremented an attempts counter (`$otp->increment('attempts')`) to enforce a maximum of 5 guesses, the absence of a database-level row lock meant that an attacker sending hundreds of simultaneous requests could potentially bypass the limit before the database processed the increments.

## Files Changed
- `app/Services/OtpService.php`

## Solution Applied
The OTP retrieval and verification process was wrapped inside a database transaction (`DB::transaction()`), and a pessimistic lock (`lockForUpdate()`) was applied to the OTP record when fetching it from the database. This ensures that concurrent requests for the same user's OTP are processed sequentially, preventing the race condition and strictly enforcing the attempt limit.

**Code Snippet (OtpService::verify):**
```php
public function verify(User $user, string $code): bool
{
    return DB::transaction(function () use ($user, $code) {
        // Lock the row for update to prevent race conditions during brute-force attempts
        $otp = Otp::where('user_id', $user->id)
            ->whereNull('used_at')
            ->latest()
            ->lockForUpdate() // Applied pessimistic lock
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
    });
}
```