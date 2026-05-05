<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiGeneration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'source_note_id',
        'source_upload_id',
        'tool',
        'title',
        'input_text',
        'output_text',
        'language',
        'tone',
        'output_length',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sourceNote()
    {
        return $this->belongsTo(Note::class, 'source_note_id');
    }

    public function sourceUpload()
    {
        return $this->belongsTo(Upload::class, 'source_upload_id');
    }
}
