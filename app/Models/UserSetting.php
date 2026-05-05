<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'appearance',
        'email_notifications',
        'push_notifications',
        'ai',
    ];

    protected $casts = [
        'appearance' => 'array',
        'email_notifications' => 'array',
        'push_notifications' => 'array',
        'ai' => 'array',
    ];
}
