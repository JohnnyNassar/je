<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    use \App\Concerns\LogsActivity;

    protected $fillable = [
        'parent_id',
        'name_en',
        'name_ar',
        'slug',
        'position',
        'is_active',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'position' => 'integer',
        'is_active' => 'boolean',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('position')->orderBy('id');
    }

    /** Top-level categories only (no parent). */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function isTopLevel(): bool
    {
        return $this->parent_id === null;
    }

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar' && $this->name_ar
            ? $this->name_ar
            : $this->name_en;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            if (! $category->slug) {
                $category->slug = Str::slug($category->name_en ?: ('category-' . time()));
            }
        });
    }
}
