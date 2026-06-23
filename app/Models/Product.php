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
     * Every image path for this product, in display order. The gallery is
     * authoritative — its order (as arranged in the admin) is used as-is. The
     * cover (image_path) is only used as a fallback when the gallery is empty.
     * De-duplicated. Returns raw storage paths, not URLs.
     *
     * @return array<int, string>
     */
    public function imagePaths(): array
    {
        $paths = [];
        foreach ((array) $this->gallery as $path) {
            if ($path) {
                $paths[] = $path;
            }
        }
        if (empty($paths) && $this->image_path) {
            $paths[] = $this->image_path;
        }
        return array_values(array_unique($paths));
    }

    /**
     * Public asset URLs for every image, in display order. Convenient for views.
     *
     * @return array<int, string>
     */
    public function imageUrls(): array
    {
        return array_map(fn (string $path) => asset('storage/' . $path), $this->imagePaths());
    }

    /**
     * The primary image URL — the first image in display order (first gallery
     * image, or the cover when the gallery is empty). Null when there is none.
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
        // Keep the gallery and the legacy image_path column in sync. The gallery
        // is the single source of truth: its first image is the main photo.
        // image_path is mirrored from it so the many places that still read the
        // cover (admin table, hero, order snapshots, variants) keep working.
        static::saving(function (self $product) {
            $gallery = array_values(array_filter((array) $product->gallery, fn ($p) => filled($p)));

            if (! empty($gallery)) {
                $product->gallery = $gallery;          // normalise to a clean ordered list
                $product->image_path = $gallery[0];    // first image = main photo
            } elseif ($product->isDirty('image_path') && filled($product->image_path)) {
                // Single-image writers (WhatsApp import, quick-add) set only
                // image_path — mirror it into the list so display is consistent.
                $product->gallery = [$product->image_path];
            }
        });

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
