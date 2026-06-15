<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOption extends Model
{
    protected $fillable = [
        'product_id',
        'name_en',
        'name_ar',
        'values',
        'position',
    ];

    protected $casts = [
        'values' => 'array',
        'position' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /** Locale-aware attribute name (e.g. "Colour" / "اللون"). */
    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();

        return $locale === 'ar' && $this->name_ar
            ? $this->name_ar
            : $this->name_en;
    }
}
