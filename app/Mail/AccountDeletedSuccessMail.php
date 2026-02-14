<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class AccountDeletedSuccessMail extends Mailable
{
    public function build()
    {
        return $this->subject('Account Deleted Successfully')
            ->markdown('emails.account_deleted_success');
    }
}
