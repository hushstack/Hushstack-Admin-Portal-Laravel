<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class AccountDeleteWarningMail extends Mailable
{
    public function build()
    {
        return $this->subject('Account Deletion Warning')
            ->markdown('emails.account_delete_warning');
    }
}
