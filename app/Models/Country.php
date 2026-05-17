<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Country extends Model
{
    /** @use HasFactory<\Database\Factories\CountryFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'dial_code',
        'flag',
        'verification_channel',
        'verification_channels',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'verification_channels' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function getVerificationChannels(): array
    {
        $channels = $this->verification_channels;
        if (is_array($channels) && $channels !== []) {
            return array_values(array_unique(array_filter($channels, fn ($value) => in_array($value, ['sms', 'whatsapp', 'email'], true))));
        }

        $legacy = (string) $this->verification_channel;

        return in_array($legacy, ['sms', 'whatsapp', 'email'], true) ? [$legacy] : ['sms'];
    }

    protected function flag(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? asset('storage/'.$value) : null
        );
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'country_product');
    }

    public function digitalProducts(): BelongsToMany
    {
        return $this->belongsToMany(DigitalProduct::class, 'country_digital_product');
    }

    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('code');
    }
}
