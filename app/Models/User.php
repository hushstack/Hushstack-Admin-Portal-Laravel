<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'phone_number',
        'password',
        'is_verified',
        'picture',
        'cover',
        'bio',
        'note',
        'birth_of_date',
        'age',
        'nationality_id',
        'contact_url',
        'address',
        'provider',
        'provider_id',
        'email_verified_at',
        'login_otp_verified_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'is_verified' => 'boolean',
        'email_verified_at' => 'datetime',
        'birth_of_date' => 'date',
        'login_otp_verified_at' => 'datetime',
    ];
}
