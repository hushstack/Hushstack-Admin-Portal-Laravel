<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoteVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'user_id',
        'title',
        'emoji',
        'content',
        'blocks_snapshot',
    ];

    protected $casts = [
        'blocks_snapshot' => 'array',
    ];

    public function note()
    {
        return $this->belongsTo(Note::class);
    }
}
