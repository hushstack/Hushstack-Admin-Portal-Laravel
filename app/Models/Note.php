<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Note extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'folder_id',
        'title',
        'emoji',
        'content',
        'is_favorite',
        'word_count',
    ];

    protected $casts = [
        'is_favorite' => 'boolean',
        'word_count' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    public function blocks()
    {
        return $this->hasMany(NoteBlock::class)->orderBy('sort_order');
    }

    public function shares()
    {
        return $this->hasMany(NoteShare::class);
    }

    public function versions()
    {
        return $this->hasMany(NoteVersion::class)->latest();
    }
}
