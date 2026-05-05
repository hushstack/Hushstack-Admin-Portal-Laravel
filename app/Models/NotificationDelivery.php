<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_id',
        'user_id',
        'title',
        'sent_to',
        'sent_at',
        'status',
        'error_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
