<?php

namespace App\Mail\Member;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserMemberRequestConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $payload) {}

    public function build()
    {
        return $this->subject('We received your member request')
            ->view('emails.member.user')
            ->with(['data' => $this->payload]);
    }
}
