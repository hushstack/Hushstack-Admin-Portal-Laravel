<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Upload extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'mime_type',
        'extension',
        'size_bytes',
        'status',
        'progress',
        'storage_path',
        'summary',
        'processing_options',
        'output_format',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'progress' => 'integer',
        'processing_options' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
