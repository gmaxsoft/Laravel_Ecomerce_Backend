<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderStatusNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order;
        $oldStatus = $event->oldStatus;
        $newStatus = $event->newStatus;

        // Logowanie zmiany statusu
        Log::info("Order status changed", [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'user_email' => $order->user->email ?? $order->shipping_email,
        ]);

        // Wysyłka emaila do klienta (można użyć Mail::to()->send() z odpowiednim mailable)
        // Przykład: Mail::to($order->shipping_email)->send(new OrderStatusChangedMail($order, $oldStatus, $newStatus));

        // Przykładowe powiadomienia dla różnych statusów
        switch ($newStatus) {
            case 'shipped':
                // Wysyłka powiadomienia o wysyłce
                Log::info("Order {$order->order_number} has been shipped");
                break;

            case 'delivered':
                // Wysyłka powiadomienia o dostarczeniu
                Log::info("Order {$order->order_number} has been delivered");
                break;

            case 'cancelled':
                // Wysyłka powiadomienia o anulowaniu
                Log::warning("Order {$order->order_number} has been cancelled");
                break;
        }
    }
}
