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

    /**
     * Bezpieczna metoda do rezerwacji stanu magazynowego
     */
    public function reserveStock(int $quantity): bool
    {
        return app(\App\Services\InventoryService::class)->reserveStock($this, $quantity);
    }

    /**
     * Bezpieczna metoda do zwolnienia rezerwacji
     */
    public function releaseReservedStock(int $quantity): void
    {
        app(\App\Services\InventoryService::class)->releaseReservedStock($this, $quantity);
    }

    /**
     * Bezpieczna metoda do potwierdzenia zamówienia
     */
    public function confirmOrder(int $quantity): void
    {
        app(\App\Services\InventoryService::class)->confirmOrder($this, $quantity);
    }

    /**
     * Bezpieczna metoda do anulowania zamówienia
     */
    public function cancelOrder(int $quantity): void
    {
        app(\App\Services\InventoryService::class)->cancelOrder($this, $quantity);
    }
}
