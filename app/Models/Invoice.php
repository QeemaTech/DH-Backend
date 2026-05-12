<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    public const PROVIDER_SADAD = 'sadad';

    public const PROVIDER_TABBY = 'tabby';

    public const PROVIDER_DEMA = 'dema';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'user_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'currency_code',
        'subtotal',
        'shipping_cost',
        'coupon_amount',
        'wallet_amount',
        'total_amount',
        'provider',
        'status',
        'provider_invoice_id',
        'provider_key',
        'payment_url',
        'paid_at',
        'expires_at',
        'provider_payload',
        'provider_response',
    ];

    protected $casts = [
        'subtotal' => 'decimal:3',
        'shipping_cost' => 'decimal:3',
        'coupon_amount' => 'decimal:3',
        'wallet_amount' => 'decimal:3',
        'total_amount' => 'decimal:3',
        'paid_at' => 'datetime',
        'expires_at' => 'date',
        'provider_payload' => 'array',
        'provider_response' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function markPaid(?\DateTimeInterface $paidAt = null): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => $paidAt ?? now(),
        ]);
    }

    public function markFailed(): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'paid_at' => null,
        ]);
    }
}
