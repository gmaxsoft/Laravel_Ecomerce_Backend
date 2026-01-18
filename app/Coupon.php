<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'minimum_amount',
        'usage_limit',
        'usage_count',
        'usage_limit_per_user',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'usage_limit_per_user' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function isValid($userId = null, $amount = null)
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && now()->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && now()->gt($this->expires_at)) {
            return false;
        }

        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        // Sprawdzenie limitu użyć na użytkownika
        if ($userId && $this->usage_limit_per_user) {
            $userUsageCount = Order::where('user_id', $userId)
                ->where('coupon_id', $this->id)
                ->count();

            if ($userUsageCount >= $this->usage_limit_per_user) {
                return false;
            }
        }

        if ($amount && $this->minimum_amount && $amount < $this->minimum_amount) {
            return false;
        }

        return true;
    }

    /**
     * Sprawdza czy kupon jest ważny i zwraca szczegółowe informacje o błędach
     */
    public function validate($userId = null, $amount = null): array
    {
        $errors = [];

        if (!$this->is_active) {
            $errors[] = 'Kupon jest nieaktywny';
        }

        if ($this->starts_at && now()->lt($this->starts_at)) {
            $errors[] = 'Kupon nie jest jeszcze aktywny';
        }

        if ($this->expires_at && now()->gt($this->expires_at)) {
            $errors[] = 'Kupon wygasł';
        }

        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            $errors[] = 'Kupon został wykorzystany maksymalną liczbę razy';
        }

        if ($userId && $this->usage_limit_per_user) {
            $userUsageCount = Order::where('user_id', $userId)
                ->where('coupon_id', $this->id)
                ->count();

            if ($userUsageCount >= $this->usage_limit_per_user) {
                $errors[] = 'Osiągnięto limit użyć tego kuponu';
            }
        }

        if ($amount && $this->minimum_amount && $amount < $this->minimum_amount) {
            $errors[] = "Minimalna kwota zamówienia dla tego kuponu to " . number_format($this->minimum_amount, 2, ',', ' ') . " zł";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    public function calculateDiscount($amount)
    {
        if ($this->type === 'percentage') {
            return ($amount * $this->value) / 100;
        }

        return min($this->value, $amount);
    }
}
