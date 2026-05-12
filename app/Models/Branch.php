<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Branch extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'vendor_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'phone',
        'is_active',
    ];

    protected $translatable = ['name'];

    protected $casts = [
        'name' => 'array',
        'is_active' => 'boolean',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
