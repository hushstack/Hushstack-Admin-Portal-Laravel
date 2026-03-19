<?php

namespace App\Mail\Contact;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserContactConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $payload) {}

    public function build()
    {
        return $this->subject('We received your message - '.config('app.name'))
            ->view('emails.contact.user')
            ->with(['data' => $this->payload]);
    }
}
