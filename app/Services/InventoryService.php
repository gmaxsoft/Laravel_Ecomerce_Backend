<?php

namespace App\Services;

use App\Product;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Rezerwuje stan magazynowy produktu z użyciem pessimistic lock
     */
    public function reserveStock(Product $product, int $quantity): bool
    {
        return DB::transaction(function () use ($product, $quantity) {
            // Użycie pessimistic lock aby zapobiec race conditions
            $product = Product::lockForUpdate()->findOrFail($product->id);

            if ($product->available_stock < $quantity) {
                return false;
            }

            $product->increment('reserved_quantity', $quantity);

            return true;
        });
    }

    /**
     * Zwolnienie zarezerwowanego stanu magazynowego
     */
    public function releaseReservedStock(Product $product, int $quantity): void
    {
        DB::transaction(function () use ($product, $quantity) {
            $product = Product::lockForUpdate()->findOrFail($product->id);

            // Upewnij się, że nie zwalniamy więcej niż zarezerwowano
            $releaseAmount = min($quantity, $product->reserved_quantity);
            
            if ($releaseAmount > 0) {
                $product->decrement('reserved_quantity', $releaseAmount);
            }
        });
    }

    /**
     * Potwierdza zamówienie - zwalnia rezerwację i zmniejsza dostępny stan
     */
    public function confirmOrder(Product $product, int $quantity): void
    {
        DB::transaction(function () use ($product, $quantity) {
            $product = Product::lockForUpdate()->findOrFail($product->id);

            // Zwolnienie rezerwacji
            $releaseAmount = min($quantity, $product->reserved_quantity);
            if ($releaseAmount > 0) {
                $product->decrement('reserved_quantity', $releaseAmount);
            }

            // Zmniejszenie dostępnego stanu
            $product->decrement('stock_quantity', $quantity);
        });
    }

    /**
     * Anuluje zamówienie - zwraca stan magazynowy
     */
    public function cancelOrder(Product $product, int $quantity): void
    {
        DB::transaction(function () use ($product, $quantity) {
            $product = Product::lockForUpdate()->findOrFail($product->id);

            // Zwrócenie stanu magazynowego
            $product->increment('stock_quantity', $quantity);
        });
    }

    /**
     * Sprawdza dostępność stanu magazynowego
     */
    public function checkAvailability(Product $product, int $quantity): bool
    {
        return $product->available_stock >= $quantity;
    }

    /**
     * Pobiera produkt z lock do aktualizacji
     */
    public function getProductForUpdate(int $productId): Product
    {
        return Product::lockForUpdate()->findOrFail($productId);
    }
}
