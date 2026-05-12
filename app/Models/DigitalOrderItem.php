<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalOrderItem extends Model
{
    protected $fillable = [
        'digital_order_id',
        'digital_product_id',
        'price',
        'quantity',
        'total',
        'notes',
        'provider_reference',
        'provider_response',
        'delivered_data',
        'delivered_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'total' => 'decimal:2',
        'provider_response' => 'array',
        'delivered_data' => 'array',
        'delivered_at' => 'datetime',
    ];

    public function digitalOrder(): BelongsTo
    {
        return $this->belongsTo(DigitalOrder::class);
    }

    public function digitalProduct(): BelongsTo
    {
        return $this->belongsTo(DigitalProduct::class);
    }
}
