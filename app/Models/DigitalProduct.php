<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

class DigitalProduct extends Model
{
    use HasTranslations;

    protected $fillable = [
        'product_id',
        'company_name',
        'merchant_id',
        'category_id',
        'sub_category_id',
        'name',
        'slug',
        'description',
        'how_to_use',
        'image',
        'cost_after_vat',
        'price',
        'currency',
        'is_active',
        'is_available',
        'visits',
        'optional_fields_exists',
        'last_update_by',
    ];

    protected $translatable = ['name', 'description', 'how_to_use'];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'how_to_use' => 'array',
        'is_active' => 'boolean',
        'is_available' => 'boolean',
    ];

    public function merchant()
    {
        return $this->belongsTo(DigitalMerchant::class);
    }

    public function category()
    {
        return $this->belongsTo(DigitalCategory::class);
    }

    public function subCategory()
    {
        return $this->belongsTo(DigitalSubCategory::class);
    }

    public function lastUpdatedBy()
    {
        return $this->belongsTo(User::class, 'last_update_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Limit to digital products available in the given country (or globally when no country rows).
     */
    public function scopeForCountry(Builder $builder, ?int $countryId): Builder
    {
        if ($countryId === null) {
            return $builder;
        }

        return $builder->where(function ($q) use ($countryId) {
            $q->whereDoesntHave('countries')
                ->orWhereHas('countries', function ($sub) use ($countryId) {
                    $sub->where('countries.id', $countryId);
                });
        });
    }

    public function isVisibleInCountry(?int $countryId): bool
    {
        if ($countryId === null) {
            return true;
        }

        if (! $this->relationLoaded('countries')) {
            if (! $this->countries()->exists()) {
                return true;
            }

            return $this->countries()->where('countries.id', $countryId)->exists();
        }

        return $this->countries->isEmpty()
            || $this->countries->contains('id', $countryId);
    }

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value
                ? asset('storage/'.$value)
                : asset('dashboard/images/product_image.jpg')
        );
    }

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'country_digital_product');
    }
}
