<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DigitalProductPurchaseLimit extends Model
{
    public const VERIFICATION_CONTACT = 'contact_verified';

    public const VERIFICATION_FULLY = 'fully_verified';

    public const PERIOD_DAILY = 'daily';

    public const PERIOD_WEEKLY = 'weekly';

    public const PERIOD_MONTHLY = 'monthly';

    protected $fillable = [
        'verification_level',
        'period_type',
        'limit_amount',
        'is_active',
    ];

    protected $casts = [
        'limit_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
