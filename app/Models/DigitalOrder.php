<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DigitalOrder extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'user_phone',
        'user_gender',
        'user_birth_date',
        'user_national_number',
        'user_national_cart_front_image',
        'user_national_cart_back_image',
        'user_national_id_expire_date',
        'user_home_address',
        'user_ip_address',
        'user_country',
        'payment_status',
        'status',
        'notes',
        'total',
        'discount',
        'shipping_cost',
        'total_cost',
    ];

    protected $casts = [
        'user_birth_date' => 'date',
        'user_national_id_expire_date' => 'date',
        'total' => 'decimal:2',
        'discount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DigitalOrderItem::class);
    }
}
