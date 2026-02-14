<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountDeletion extends Model
{
    protected $fillable = ['user_id', 'execute_at'];

    protected $casts = [
        'execute_at' => 'datetime',
    ];
}
