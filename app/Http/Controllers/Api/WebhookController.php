<?php

namespace App\Http\Controllers\Api;

use App\Order;
use App\Payment;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Webhook;


class WebhookController
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    
    public function handleWebhook(Request $request): JsonResponse
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook.secret');

        try {
            // Weryfikacja sygnatury webhooka
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                $webhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe Webhook Error: Invalid payload', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Stripe Webhook Error: Invalid signature', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        Log::info('Stripe Webhook Received: ' . $event->type, ['event_id' => $event->id]);

        // Obsługa różnych typów eventów
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                $this->handlePaymentIntentSucceeded($paymentIntent);
                break;

            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                $this->handlePaymentIntentFailed($paymentIntent);
                break;

            case 'payment_intent.canceled':
                $paymentIntent = $event->data->object;
                $this->handlePaymentIntentCanceled($paymentIntent);
                break;

            case 'charge.refunded':
                $charge = $event->data->object;
                $this->handleChargeRefunded($charge);
                break;

            default:
                Log::info('Stripe Webhook: Unhandled event type', ['event_type' => $event->type]);
                break;
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Obsługa udanej płatności.
     */
    protected function handlePaymentIntentSucceeded(PaymentIntent $paymentIntent): void
    {
        $order = Order::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if ($order) {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing', // Zmieniamy status zamówienia na przetwarzane
            ]);

            Payment::create([
                'order_id' => $order->id,
                'payment_method' => 'stripe',
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $paymentIntent->amount / 100, // Kwota w centach, konwertujemy na walutę
                'currency' => strtoupper($paymentIntent->currency),
                'status' => 'succeeded',
                'paid_at' => now(),
                'metadata' => [
                    'charge_id' => $paymentIntent->charges->data[0]->id ?? null,
                ],
            ]);

            // Potwierdź zamówienie i zmniejsz stan magazynowy
            foreach ($order->items as $orderItem) {
                $this->inventoryService->confirmOrder($orderItem->product, $orderItem->quantity);
            }

            Log::info('Stripe Webhook: PaymentIntent succeeded for order ' . $order->id, ['payment_intent_id' => $paymentIntent->id]);
        } else {
            Log::warning('Stripe Webhook: PaymentIntent succeeded for unknown order', ['payment_intent_id' => $paymentIntent->id]);
        }
    }

    /**
     * Obsługa nieudanej płatności.
     */
    protected function handlePaymentIntentFailed(PaymentIntent $paymentIntent): void
    {
        $order = Order::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if ($order) {
            $order->update([
                'payment_status' => 'failed',
                'status' => 'cancelled', // Anulujemy zamówienie
            ]);

            Payment::create([
                'order_id' => $order->id,
                'payment_method' => 'stripe',
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $paymentIntent->amount / 100,
                'currency' => strtoupper($paymentIntent->currency),
                'status' => 'failed',
                'failure_reason' => $paymentIntent->last_payment_error->message ?? 'Unknown error',
            ]);

            // Zwolnij zarezerwowany stan magazynowy
            foreach ($order->items as $orderItem) {
                $this->inventoryService->releaseReservedStock($orderItem->product, $orderItem->quantity);
            }

            Log::warning('Stripe Webhook: PaymentIntent failed for order ' . $order->id, ['payment_intent_id' => $paymentIntent->id]);
        } else {
            Log::warning('Stripe Webhook: PaymentIntent failed for unknown order', ['payment_intent_id' => $paymentIntent->id]);
        }
    }

    /**
     * Obsługa anulowanej płatności.
     */
    protected function handlePaymentIntentCanceled(PaymentIntent $paymentIntent): void
    {
        $order = Order::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if ($order) {
            $order->update([
                'payment_status' => 'canceled',
                'status' => 'cancelled', // Anulujemy zamówienie
            ]);

            // Zwolnij zarezerwowany stan magazynowy
            foreach ($order->items as $orderItem) {
                $this->inventoryService->releaseReservedStock($orderItem->product, $orderItem->quantity);
            }

            Log::info('Stripe Webhook: PaymentIntent canceled for order ' . $order->id, ['payment_intent_id' => $paymentIntent->id]);
        } else {
            Log::warning('Stripe Webhook: PaymentIntent canceled for unknown order', ['payment_intent_id' => $paymentIntent->id]);
        }
    }

    /**
     * Obsługa zwrotu płatności.
     */
    protected function handleChargeRefunded($charge): void
    {
        $payment = Payment::whereJsonContains('metadata->charge_id', $charge->id)->first();

        if ($payment) {
            $payment->update(['status' => 'refunded']);
            $order = $payment->order;

            if ($order) {
                $order->update(['payment_status' => 'refunded']);

                // Zwróć stan magazynowy
                foreach ($order->items as $orderItem) {
                    $this->inventoryService->cancelOrder($orderItem->product, $orderItem->quantity);
                }
            }
            Log::info('Stripe Webhook: Charge refunded for payment ' . $payment->id, ['charge_id' => $charge->id]);
        } else {
            Log::warning('Stripe Webhook: Charge refunded for unknown payment', ['charge_id' => $charge->id]);
        }
    }
}
