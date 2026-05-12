<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class DigitalCategory extends Model
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
     * Scope a query to only include active digital categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the user who last updated the digital category
     */
    public function lastUpdatedBy()
    {
        return $this->belongsTo(User::class, 'last_update_by');
    }
    public function products()
    {
        return $this->hasMany(DigitalProduct::class,'category_id');
    }
}
