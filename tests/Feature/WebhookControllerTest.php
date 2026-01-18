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

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Stripe configs
        $this->app['config']->set('services.stripe.secret', 'sk_test_mock');
        $this->app['config']->set('services.stripe.webhook.secret', 'whsec_test_mock');
        $this->app['config']->set('cashier.currency', 'usd');
    }

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

            // Mock statycznej metody Webhook::constructEvent
            $eventObject = json_decode($payload, false);
            $mockWebhook = Mockery::mock('alias:Stripe\Webhook');
            $mockWebhook->shouldReceive('constructEvent')
                ->once()
                ->with($payload, 'test_signature', 'whsec_test_mock')
                ->andReturn($eventObject);

            $response = $this->call('POST', '/api/webhooks/stripe', [], [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => 'test_signature',
            ], $payload);

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

        // Mock statycznej metody Webhook::constructEvent do rzucenia wyjątku
        $mockWebhook = Mockery::mock('alias:Stripe\Webhook');
        $mockWebhook->shouldReceive('constructEvent')
            ->once()
            ->andThrow(new \UnexpectedValueException('Invalid payload'));

        $response = $this->call('POST', '/api/webhooks/stripe', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 'invalid_signature',
        ], $payload);

        $response->assertStatus(400)
            ->assertJson(['error' => 'Invalid payload']);
    }
}
