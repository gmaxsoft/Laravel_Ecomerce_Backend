<?php

namespace App\Http\Controllers\Api;

use App\Order;
use App\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\PaymentIntent;
use Stripe\Webhook;

class WebhookController
{
    /**
     * Obsługa webhooków Stripe
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            // Weryfikacja sygnatury webhooka
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                $webhookSecret
            );
        } catch (\Exception $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Obsługa różnych typów eventów
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event->data->object);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($event->data->object);
                break;

            case 'payment_intent.canceled':
                $this->handlePaymentIntentCanceled($event->data->object);
                break;

            case 'charge.refunded':
                $this->handleChargeRefunded($event->data->object);
                break;

            default:
                Log::info('Unhandled Stripe webhook event', [
                    'type' => $event->type,
                ]);
        }

        return response()->json(['received' => true]);
    }

    /**
     * Obsługa udanej płatności
     */
    protected function handlePaymentIntentSucceeded(PaymentIntent $paymentIntent): void
    {
        $paymentIntentId = $paymentIntent->id;

        Log::info('Payment intent succeeded', [
            'payment_intent_id' => $paymentIntentId,
            'amount' => $paymentIntent->amount,
        ]);

        // Znajdź zamówienie po payment_intent_id
        $order = Order::where('stripe_payment_intent_id', $paymentIntentId)->first();

        if ($order) {
            // Aktualizacja statusu zamówienia
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing', // Zmiana statusu na przetwarzane
            ]);

            // Utworzenie rekordu płatności
            Payment::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'payment_intent_id' => $paymentIntentId,
                ],
                [
                    'payment_method' => 'stripe',
                    'amount' => $paymentIntent->amount / 100, // Stripe używa centów
                    'currency' => strtoupper($paymentIntent->currency),
                    'status' => 'succeeded',
                    'paid_at' => now(),
                    'metadata' => [
                        'charge_id' => $paymentIntent->charges->data[0]->id ?? null,
                    ],
                ]
            );

            Log::info('Order payment status updated', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_status' => 'paid',
            ]);
        }
    }

    /**
     * Obsługa nieudanej płatności
     */
    protected function handlePaymentIntentFailed(PaymentIntent $paymentIntent): void
    {
        $paymentIntentId = $paymentIntent->id;

        Log::warning('Payment intent failed', [
            'payment_intent_id' => $paymentIntentId,
            'last_payment_error' => $paymentIntent->last_payment_error,
        ]);

        $order = Order::where('stripe_payment_intent_id', $paymentIntentId)->first();

        if ($order) {
            $order->update([
                'payment_status' => 'failed',
            ]);

            // Utworzenie rekordu płatności z statusem failed
            Payment::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'payment_intent_id' => $paymentIntentId,
                ],
                [
                    'payment_method' => 'stripe',
                    'amount' => $paymentIntent->amount / 100,
                    'currency' => strtoupper($paymentIntent->currency),
                    'status' => 'failed',
                    'failure_reason' => $paymentIntent->last_payment_error->message ?? 'Unknown error',
                ]
            );
        }
    }

    /**
     * Obsługa anulowanej płatności
     */
    protected function handlePaymentIntentCanceled(PaymentIntent $paymentIntent): void
    {
        $paymentIntentId = $paymentIntent->id;

        $order = Order::where('stripe_payment_intent_id', $paymentIntentId)->first();

        if ($order) {
            $order->update([
                'payment_status' => 'cancelled',
                'status' => 'cancelled',
            ]);

            // Zwrócenie stanu magazynowego
            foreach ($order->items as $item) {
                $product = $item->product;
                if ($product) {
                    $product->cancelOrder($item->quantity);
                }
            }
        }
    }

    /**
     * Obsługa zwrotu płatności
     */
    protected function handleChargeRefunded($charge): void
    {
        $chargeId = $charge->id;

        Log::info('Charge refunded', [
            'charge_id' => $chargeId,
            'amount_refunded' => $charge->amount_refunded,
        ]);

        // Znajdź płatność po charge_id
        $payment = Payment::whereJsonContains('metadata->charge_id', $chargeId)->first();

        if ($payment) {
            $payment->update([
                'status' => 'refunded',
            ]);

            $order = $payment->order;
            if ($order) {
                $order->update([
                    'payment_status' => 'refunded',
                    'status' => 'refunded',
                ]);

                // Zwrócenie stanu magazynowego
                foreach ($order->items as $item) {
                    $product = $item->product;
                    if ($product) {
                        $product->cancelOrder($item->quantity);
                    }
                }
            }
        }
    }
}
