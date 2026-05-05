<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoteBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'type',
        'content',
        'checked',
        'sort_order',
    ];

    protected $casts = [
        'checked' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function note()
    {
        return $this->belongsTo(Note::class);
    }
}
