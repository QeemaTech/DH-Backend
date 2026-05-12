<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class DigitalMerchant extends Model
{
    use HasTranslations;

    protected $fillable = [
        'merchant_id',
        'company_name',
        'name',
        'description',
        'redeem_steps',
        'terms',
        'parent_id',
        'last_synced_at',
    ];

    protected $translatable = ['name', 'description', 'redeem_steps', 'terms'];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'redeem_steps' => 'array',
        'terms' => 'array',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get the parent merchant
     */
    public function parent()
    {
        return $this->belongsTo(DigitalMerchant::class, 'parent_id');
    }

    /**
     * Get the child merchants
     */
    public function children()
    {
        return $this->hasMany(DigitalMerchant::class, 'parent_id');
    }
}
