<?php

namespace Tests\Feature;

use App\Cart;
use App\CartItem;
use App\Coupon;
use App\Order;
use App\Product;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Stripe API Key
        $this->app['config']->set('services.stripe.secret', 'sk_test_mock');
        $this->app['config']->set('cashier.currency', 'usd');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_order_with_stripe_payment_intent(): void
    {
        DB::beginTransaction();

        try {
            $user = User::factory()->create();
            $product = Product::factory()->create([
                'stock_quantity' => 10,
                'reserved_quantity' => 0,
                'price' => 100.00,
            ]);

            $cart = Cart::create([
                'user_id' => $user->id,
                'total' => 100.00,
            ]);

            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => 100.00,
                'subtotal' => 100.00,
            ]);

            // Mock Stripe PaymentIntent
            $mockPaymentIntent = Mockery::mock('alias:' . PaymentIntent::class);
            $mockPaymentIntent->shouldReceive('create')
                ->once()
                ->andReturn((object) [
                    'id' => 'pi_test_123',
                    'client_secret' => 'pi_test_123_secret',
                    'amount' => 11000, // $110.00 in cents (100 + 10% tax)
                    'currency' => 'usd',
                    'charges' => (object) [
                        'data' => [],
                    ],
                ]);

            // Mock Stripe facade
            $this->app->instance(Stripe::class, Mockery::mock(Stripe::class));

            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/orders', [
                    'cart_id' => $cart->id,
                    'shipping_name' => 'Test User',
                    'shipping_email' => 'test@example.com',
                    'shipping_address' => '123 Test St',
                    'shipping_city' => 'Test City',
                    'shipping_postal_code' => '12345',
                    'shipping_country' => 'US',
                ]);

            DB::rollBack(); // Rollback dla testu - nie chcemy zapisywać danych

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'data' => [
                        'id',
                        'order_number',
                        'status',
                        'total',
                    ],
                    'payment_intent' => [
                        'client_secret',
                        'id',
                    ],
                ]);
        } finally {
            DB::rollBack();
        }
    }

    /** @test */
    public function it_validates_coupon_when_creating_order(): void
    {
        DB::beginTransaction();

        try {
            $user = User::factory()->create();
            $product = Product::factory()->create([
                'stock_quantity' => 10,
                'reserved_quantity' => 0,
                'price' => 100.00,
            ]);

            $coupon = Coupon::factory()->create([
                'code' => 'TEST10',
                'type' => 'percentage',
                'value' => 10,
                'is_active' => true,
                'usage_limit' => 100,
                'usage_count' => 0,
            ]);

            $cart = Cart::create([
                'user_id' => $user->id,
                'total' => 100.00,
            ]);

            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => 100.00,
                'subtotal' => 100.00,
            ]);

            // Mock Stripe PaymentIntent
            $mockPaymentIntent = Mockery::mock('alias:' . PaymentIntent::class);
            $mockPaymentIntent->shouldReceive('create')
                ->once()
                ->andReturn((object) [
                    'id' => 'pi_test_123',
                    'client_secret' => 'pi_test_123_secret',
                    'amount' => 9900, // $99.00 in cents (100 + 10% tax - 10% discount)
                    'currency' => 'usd',
                ]);

            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/orders', [
                    'cart_id' => $cart->id,
                    'coupon_code' => 'TEST10',
                    'shipping_name' => 'Test User',
                    'shipping_email' => 'test@example.com',
                    'shipping_address' => '123 Test St',
                    'shipping_city' => 'Test City',
                    'shipping_postal_code' => '12345',
                    'shipping_country' => 'US',
                ]);

            DB::rollBack();

            $response->assertStatus(201)
                ->assertJsonPath('data.discount', 10);
        } finally {
            DB::rollBack();
        }
    }
}
