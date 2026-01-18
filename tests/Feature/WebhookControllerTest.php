<?php

namespace Tests\Feature;

use App\Order;
use App\OrderItem;
use App\Payment;
use App\Product;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Stripe\Webhook;
use Tests\TestCase;

class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_handles_payment_intent_succeeded_webhook(): void
    {
        DB::beginTransaction();

        try {
            $user = User::factory()->create();
            $product = Product::factory()->create([
                'stock_quantity' => 10,
                'reserved_quantity' => 5, // Założona rezerwacja
            ]);

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => 'ORD-TEST123',
                'status' => 'pending',
                'subtotal' => 100.00,
                'tax' => 10.00,
                'shipping' => 0,
                'discount' => 0,
                'total' => 110.00,
                'payment_status' => 'pending',
                'stripe_payment_intent_id' => 'pi_test_123',
                'shipping_name' => 'Test User',
                'shipping_email' => 'test@example.com',
                'shipping_address' => '123 Test St',
                'shipping_city' => 'Test City',
                'shipping_postal_code' => '12345',
                'shipping_country' => 'US',
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'quantity' => 2,
                'price' => 50.00,
                'subtotal' => 100.00,
            ]);

            // Mock webhook payload
            $payload = json_encode([
                'id' => 'evt_test_123',
                'type' => 'payment_intent.succeeded',
                'data' => [
                    'object' => [
                        'id' => 'pi_test_123',
                        'amount' => 11000,
                        'currency' => 'usd',
                        'charges' => [
                            'data' => [
                                [
                                    'id' => 'ch_test_123',
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

            // Mock webhook signature verification
            $this->app->bind(Webhook::class, function () use ($payload) {
                $mock = Mockery::mock(Webhook::class);
                $mock->shouldReceive('constructEvent')
                    ->once()
                    ->andReturn(json_decode($payload));

                return $mock;
            });

            $response = $this->postJson('/api/webhooks/stripe', [], [
                'Stripe-Signature' => 'test_signature',
            ]);

            DB::rollBack();

            $response->assertStatus(200)
                ->assertJson(['status' => 'success']);
        } finally {
            DB::rollBack();
        }
    }

    /** @test */
    public function it_rejects_invalid_webhook_signature(): void
    {
        $payload = 'invalid_payload';

        // Mock webhook signature verification failure
        $this->app->bind(Webhook::class, function () {
            $mock = Mockery::mock(Webhook::class);
            $mock->shouldReceive('constructEvent')
                ->once()
                ->andThrow(new \UnexpectedValueException('Invalid payload'));

            return $mock;
        });

        $response = $this->postJson('/api/webhooks/stripe', [], [
            'Stripe-Signature' => 'invalid_signature',
        ]);

        $response->assertStatus(400)
            ->assertJson(['error' => 'Invalid signature']);
    }
}
