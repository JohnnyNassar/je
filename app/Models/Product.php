<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name_en',
        'name_ar',
        'description_en',
        'description_ar',
        'price',
        'compare_at_price',
        'stock',
        'image_path',
        'is_active',
        'is_featured',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'stock' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar' && $this->name_ar
            ? $this->name_ar
            : $this->name_en;
    }

    public function getDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $locale === 'ar' && $this->description_ar
            ? $this->description_ar
            : $this->description_en;
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position')->orderBy('id');
    }

    public function hasVariants(): bool
    {
        return $this->variants()->exists();
    }

    /**
     * Keep products.stock as the sum of its variants' stock, so all the existing
     * stock-based queries, scopes, and badges keep working unchanged. No-op when
     * the product has no variants (its own stock is the source of truth then).
     */
    public function syncStockFromVariants(): void
    {
        if (! $this->variants()->exists()) {
            return;
        }

        $sum = (int) $this->variants()->sum('stock');

        if ((int) $this->stock !== $sum) {
            $this->forceFill(['stock' => $sum])->saveQuietly();
        }
    }

    public function isOnSale(): bool
    {
        return $this->compare_at_price !== null
            && (float) $this->compare_at_price > (float) $this->price;
    }

    public function getDiscountPercentageAttribute(): ?int
    {
        if (! $this->isOnSale()) {
            return null;
        }
        $diff = (float) $this->compare_at_price - (float) $this->price;
        return (int) round(($diff / (float) $this->compare_at_price) * 100);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    protected static function booted(): void
    {
        static::saved(function (self $product) {
            if ($product->wasChanged('image_path') && $product->image_path) {
                $abs = storage_path('app/public/' . ltrim($product->image_path, '/'));
                \App\Support\ImageResizer::fit($abs, 1600, 85);
            }
        });
    }
}
