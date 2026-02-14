@component('mail::message')
# Your OTP Code

Your OTP code is:

@component('mail::panel')
{{ $code }}
@endcomponent

This code will expire in **{{ $minutes }} minutes**.

If you didn’t request this, please ignore this email.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
