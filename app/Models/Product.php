<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    use \App\Concerns\LogsActivity;

    protected $fillable = [
        'category_id',
        'name_en',
        'name_ar',
        'description_en',
        'description_ar',
        'price',
        'cost_price',
        'compare_at_price',
        'stock',
        'image_path',
        'gallery',
        'is_active',
        'is_featured',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'stock' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'gallery' => 'array',
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

    /**
     * Every image path for this product — the cover (image_path) first, then any
     * gallery images, de-duplicated. Returns raw storage paths, not URLs.
     *
     * @return array<int, string>
     */
    public function imagePaths(): array
    {
        $paths = [];
        if ($this->image_path) {
            $paths[] = $this->image_path;
        }
        foreach ((array) $this->gallery as $path) {
            if ($path) {
                $paths[] = $path;
            }
        }
        return array_values(array_unique($paths));
    }

    /**
     * Public asset URLs for every image, cover first. Convenient for views.
     *
     * @return array<int, string>
     */
    public function imageUrls(): array
    {
        return array_map(fn (string $path) => asset('storage/' . $path), $this->imagePaths());
    }

    /**
     * The primary image URL — the cover, or the first gallery image when there
     * is no cover. Null when there is none.
     */
    public function mainImageUrl(): ?string
    {
        return $this->imageUrls()[0] ?? null;
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

    /** Structured option axes (Colour, Size, …) that drive per-axis selectors. */
    public function options()
    {
        return $this->hasMany(ProductOption::class)->orderBy('position')->orderBy('id');
    }

    public function hasOptions(): bool
    {
        return $this->options()->exists();
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

    /**
     * Profit per unit (price − cost). Null when no cost has been recorded.
     */
    public function getProfitAttribute(): ?float
    {
        if ($this->cost_price === null) {
            return null;
        }
        return round((float) $this->price - (float) $this->cost_price, 2);
    }

    /**
     * Profit margin as a percentage of the selling price. Null when no cost
     * has been recorded or the price is zero.
     */
    public function getMarginPercentageAttribute(): ?int
    {
        if ($this->cost_price === null || (float) $this->price <= 0) {
            return null;
        }
        return (int) round(($this->profit / (float) $this->price) * 100);
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
            if ($product->wasChanged('gallery')) {
                foreach ((array) $product->gallery as $path) {
                    if (! $path) {
                        continue;
                    }
                    $abs = storage_path('app/public/' . ltrim($path, '/'));
                    \App\Support\ImageResizer::fit($abs, 1600, 85);
                }
            }
        });
    }
}
