<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    public const DEFAULT_SLUG = 'user';
    public const ADMIN_SLUG = 'admin';
    public const USER_SLUG = 'user';

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public static function defaultId(): int
    {
        return (int) (static::query()
            ->where('slug', self::DEFAULT_SLUG)
            ->value('id') ?? 0);
    }

    public static function idBySlug(string $slug): int
    {
        return (int) (static::query()
            ->where('slug', $slug)
            ->value('id') ?? 0);
    }
}
