<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class DigitalSubCategory extends Model
{
    use HasTranslations, Sluggable;

    protected $fillable = [
        'name',
        'slug',
        'image',
        'thumbnail',
        'visits',
        'is_active',
        'last_update_by',
        'digital_category_id',
    ];

    protected $translatable = ['name'];

    protected $casts = [
        'name' => 'array',
        'is_active' => 'boolean',
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name.en',
            ],
        ];
    }

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value
                ? asset('storage/'.$value)
                : asset('dashboard/images/category_image.png')
        );
    }

    /**
     * Get the digital category that the sub category belongs to
     */
    public function digitalCategory()
    {
        return $this->belongsTo(DigitalCategory::class);
    }

    /**
     * Get the user who last updated the digital sub category
     */
    public function lastUpdatedBy()
    {
        return $this->belongsTo(User::class, 'last_update_by');
    }

    /**
     * Scope a query to only include active digital sub categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
