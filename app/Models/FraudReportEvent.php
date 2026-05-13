<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FraudReportEvent extends Model
{
    protected $fillable = [
        'fraud_report_id',
        'event_type',
        'actor_type',
        'actor_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function fraudReport(): BelongsTo
    {
        return $this->belongsTo(FraudReport::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
