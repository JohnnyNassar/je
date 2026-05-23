<?php

namespace App\Models;

use App\Support\ImageResizer;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'stock',
        'price',
        'image_path',
        'position',
    ];

    protected $casts = [
        'stock' => 'integer',
        'price' => 'decimal:2',
        'position' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /** Selling price for this variant — its override, or the product's price. */
    public function effectivePrice(): float
    {
        return $this->price !== null
            ? (float) $this->price
            : (float) ($this->product?->price ?? 0);
    }

    /** Image for this variant — its own, or the product's. */
    public function effectiveImagePath(): ?string
    {
        return $this->image_path ?: $this->product?->image_path;
    }

    protected static function booted(): void
    {
        static::saved(function (self $variant) {
            if ($variant->wasChanged('image_path') && $variant->image_path) {
                $abs = storage_path('app/public/' . ltrim($variant->image_path, '/'));
                ImageResizer::fit($abs, 1600, 85);
            }

            $variant->product?->syncStockFromVariants();
        });

        static::deleted(function (self $variant) {
            $variant->product?->syncStockFromVariants();
        });
    }
}
