<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'price',
        'sale_price',
        'category',
        'size',
        'condition',
        'brand',
        'color',
        'images',
        'stock_quantity',
        'reserved_quantity',
        'is_active',
        'sku',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'images' => 'array',
        'stock_quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'is_active' => 'boolean',
    ];

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getAvailableStockAttribute()
    {
        return $this->stock_quantity - $this->reserved_quantity;
    }

    public function getCurrentPriceAttribute()
    {
        return $this->sale_price ?? $this->price;
    }
}
