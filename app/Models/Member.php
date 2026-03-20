<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'position_id',
        'long_description',
        'skills',
        'is_published',
    ];

    protected $casts = [
        'skills' => 'array',
        'is_published' => 'boolean',
        'user_id' => 'integer',
        'position_id' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }
}
