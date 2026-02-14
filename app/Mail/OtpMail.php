<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class OtpMail extends Mailable
{
    public function __construct(public string $code, public int $minutes) {}

    public function build()
    {
        return $this->subject('Your OTP Code')
            ->markdown('emails.otp', [
                'code' => $this->code,
                'minutes' => $this->minutes,
            ]);
    }
}
