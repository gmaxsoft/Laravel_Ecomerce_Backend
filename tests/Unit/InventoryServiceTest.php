<?php

namespace Tests\Unit;

use App\Product;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryService = new InventoryService();
    }

    /** @test */
    public function it_reserves_stock_successfully(): void
    {
        DB::beginTransaction();

        try {
            $product = Product::factory()->create([
                'stock_quantity' => 10,
                'reserved_quantity' => 0,
            ]);

            $result = $this->inventoryService->reserveStock($product, 5);

            $this->assertTrue($result);

            $product->refresh();
            $this->assertEquals(5, $product->reserved_quantity);
            $this->assertEquals(5, $product->available_stock);
        } finally {
            DB::rollBack();
        }
    }

    /** @test */
    public function it_fails_to_reserve_stock_when_insufficient_available(): void
    {
        DB::beginTransaction();

        try {
            $product = Product::factory()->create([
                'stock_quantity' => 10,
                'reserved_quantity' => 8, // Tylko 2 dostępne
            ]);

            $result = $this->inventoryService->reserveStock($product, 5);

            $this->assertFalse($result);

            $product->refresh();
            $this->assertEquals(8, $product->reserved_quantity); // Nie zmienione
        } finally {
            DB::rollBack();
        }
    }

    /** @test */
    public function it_releases_reserved_stock(): void
    {
        DB::beginTransaction();

        try {
            $product = Product::factory()->create([
                'stock_quantity' => 10,
                'reserved_quantity' => 5,
            ]);

            $this->inventoryService->releaseReservedStock($product, 3);

            $product->refresh();
            $this->assertEquals(2, $product->reserved_quantity);
            $this->assertEquals(8, $product->available_stock);
        } finally {
            DB::rollBack();
        }
    }

    /** @test */
    public function it_confirms_order_and_decreases_stock(): void
    {
        DB::beginTransaction();

        try {
            $product = Product::factory()->create([
                'stock_quantity' => 10,
                'reserved_quantity' => 5,
            ]);

            $this->inventoryService->confirmOrder($product, 3);

            $product->refresh();
            $this->assertEquals(2, $product->reserved_quantity);
            $this->assertEquals(7, $product->stock_quantity);
        } finally {
            DB::rollBack();
        }
    }

    /** @test */
    public function it_cancels_order_and_returns_stock(): void
    {
        DB::beginTransaction();

        try {
            $product = Product::factory()->create([
                'stock_quantity' => 10,
                'reserved_quantity' => 0,
            ]);

            $this->inventoryService->cancelOrder($product, 3);

            $product->refresh();
            $this->assertEquals(13, $product->stock_quantity);
        } finally {
            DB::rollBack();
        }
    }

    /** @test */
    public function it_checks_availability_correctly(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'reserved_quantity' => 3,
        ]);

        $this->assertTrue($this->inventoryService->checkAvailability($product, 5)); // 7 dostępne
        $this->assertFalse($this->inventoryService->checkAvailability($product, 10)); // Tylko 7 dostępne
    }
}
