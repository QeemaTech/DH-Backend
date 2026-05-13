<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FraudReport extends Model
{
    protected $fillable = [
        'full_name',
        'company_name',
        'email',
        'phone_number',
        'card_last4',
        'card_type',
        'fraud_description',
        'status',
        'assigned_to',
        'resolved_at',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function events(): HasMany
    {
        return $this->hasMany(FraudReportEvent::class);
    }
}
