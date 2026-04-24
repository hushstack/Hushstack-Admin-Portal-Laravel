<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CliLoginRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_CONSUMED = 'consumed';
    public const STATUS_DENIED = 'denied';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'device_code_hash',
        'user_code_hash',
        'status',
        'user_id',
        'requested_abilities',
        'client_name',
        'client_version',
        'device_name',
        'ip_address',
        'user_agent',
        'approved_at',
        'expires_at',
        'last_polled_at',
        'consumed_at',
    ];

    protected $casts = [
        'requested_abilities' => 'array',
        'approved_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_polled_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
