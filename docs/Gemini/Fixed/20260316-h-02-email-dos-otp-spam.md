# Security Issue: Email Bombing / OTP Denial of Service (DoS)

**ID:** 20260316-h-02-email-dos-otp-spam
**Severity:** HIGH
**Date Fixed:** March 16, 2026

## Problem Description
The application lacked sufficient rate limiting and logic enforcement on the `/api/auth/forgot-password` endpoint. When invoked, it directly called the `OtpService::send()` method, which forcefully deleted any existing active OTP and immediately queued a new email. It bypassed the 60-second cooldown intended to prevent spam.

An attacker could exploit this by repeatedly calling the endpoint, flooding the target user's inbox with OTP emails, and potentially exhausting the application's SMTP quota or causing the domain to be blacklisted.

## Files Changed
- `routes/api.php`
- `app/Services/OtpService.php`

## Solution Applied
1. **Route Throttling:** Applied Laravel's built-in `throttle` middleware to the sensitive authentication routes in `routes/api.php` to prevent volumetric attacks.
   - `/login` -> `throttle:5,1`
   - `/verify-email-otp` -> `throttle:5,1`
   - `/resend-otp` -> `throttle:3,1`
   - `/forgot-password` -> `throttle:3,1`

2. **Service-Level Cooldown Enforcement:** Updated the `OtpService::send()` method to explicitly enforce the `resendCooldownSeconds` logic. If an OTP was sent recently, the method now silently returns without dispatching a new email.

**Code Snippet (OtpService::send):**
```php
public function send(User $user, int $minutes = 10): void
{
    // Enforce cooldown to prevent email bombing (DoS)
    $recentOtp = Otp::where('user_id', $user->id)
        ->whereNull('used_at')
        ->latest()
        ->first();

    if ($recentOtp && $recentOtp->last_sent_at && now()->diffInSeconds($recentOtp->last_sent_at) < $this->resendCooldownSeconds) {
        // Silently return to prevent enumeration and stop sending if within cooldown
        return;
    }
    // ... continues to generate and send OTP
}
```