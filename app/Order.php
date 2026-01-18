<?php

namespace App;

use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Jobs\GenerateInvoicePdf;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected static $statusChanges = [];

    protected $fillable = [
        'order_number',
        'user_id',
        'status',
        'subtotal',
        'tax',
        'shipping',
        'discount',
        'total',
        'coupon_id',
        'shipping_name',
        'shipping_email',
        'shipping_phone',
        'shipping_address',
        'shipping_city',
        'shipping_postal_code',
        'shipping_country',
        'payment_method',
        'payment_status',
        'stripe_payment_intent_id',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . strtoupper(uniqid());
            }
        });

        static::created(function ($order) {
            // Wywołanie eventu OrderCreated
            event(new OrderCreated($order));

            // Dodanie job do kolejki dla generowania PDF faktury
            GenerateInvoicePdf::dispatch($order);
        });

        static::updating(function ($order) {
            // Sprawdzenie czy status się zmienił
            if ($order->isDirty('status')) {
                // Przechowujemy stary status w zmiennej statycznej, klucz to ID zamówienia
                static::$statusChanges[$order->id] = $order->getOriginal('status');
            }
        });

        static::updated(function ($order) {
            // Wywołanie eventu zmiany statusu po aktualizacji
            if (isset(static::$statusChanges[$order->id])) {
                $oldStatus = static::$statusChanges[$order->id];
                event(new OrderStatusChanged($order, $oldStatus, $order->status));
                unset(static::$statusChanges[$order->id]);
            }
        });
    }
}
