<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'scheduled_for',
        'email_enabled',
        'status',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'email_enabled' => 'boolean',
    ];

    public function deliveries()
    {
        return $this->hasMany(NotificationDelivery::class, 'notification_id');
    }
}
