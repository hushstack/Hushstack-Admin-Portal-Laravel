<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'sku',
        'short_description',
        'description',
        'image',
        'price',
        'qty',
        'category_id',
        'brand_id',
        'user_id',
        'is_stock',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'qty' => 'integer',
        'category_id' => 'integer',
        'brand_id' => 'integer',
        'user_id' => 'integer',
        'is_stock' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
