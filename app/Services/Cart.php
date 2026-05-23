<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Session cart. Each line is keyed by product (+ variant), so the same product
 * can appear once per variant. Raw shape:
 *   ['<key>' => ['product_id' => int, 'variant_id' => ?int, 'qty' => int]]
 * where <key> is "{productId}-{variantId}" for a variant, or "{productId}".
 */
class Cart
{
    private const SESSION_KEY = 'cart';

    public function add(int $productId, ?int $variantId, int $quantity = 1): void
    {
        $items = $this->raw();
        $key = $this->key($productId, $variantId);
        $qty = ($items[$key]['qty'] ?? 0) + $quantity;

        if ($qty <= 0) {
            unset($items[$key]);
        } else {
            $items[$key] = ['product_id' => $productId, 'variant_id' => $variantId, 'qty' => $qty];
        }

        $this->store($items);
    }

    public function set(string $key, int $quantity): void
    {
        $items = $this->raw();

        if (! isset($items[$key])) {
            return;
        }

        if ($quantity <= 0) {
            unset($items[$key]);
        } else {
            $items[$key]['qty'] = $quantity;
        }

        $this->store($items);
    }

    public function remove(string $key): void
    {
        $items = $this->raw();
        unset($items[$key]);
        $this->store($items);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function raw(): array
    {
        return session(self::SESSION_KEY, []);
    }

    public function itemCount(): int
    {
        return array_sum(array_map(fn ($line) => $line['qty'] ?? 0, $this->raw()));
    }

    public function isEmpty(): bool
    {
        return $this->itemCount() === 0;
    }

    /**
     * Resolves the raw cart into display rows, clamping each quantity to the
     * available stock (per variant when set) and dropping anything that no
     * longer exists or is out of stock.
     */
    public function items(): Collection
    {
        $raw = $this->raw();

        if (empty($raw)) {
            return collect();
        }

        $productIds = collect($raw)->pluck('product_id')->unique()->all();
        $products = Product::with('variants')->whereIn('id', $productIds)->get()->keyBy('id');

        return collect($raw)
            ->map(function ($line, $key) use ($products) {
                $product = $products->get($line['product_id']);

                if (! $product) {
                    return null;
                }

                $variant = null;
                if (! empty($line['variant_id'])) {
                    $variant = $product->variants->firstWhere('id', $line['variant_id']);
                    if (! $variant) {
                        return null; // variant was deleted
                    }
                }

                $available = $variant ? (int) $variant->stock : (int) $product->stock;
                $qty = max(0, min((int) $line['qty'], $available));

                if ($qty === 0) {
                    return null;
                }

                $unitPrice = $variant ? $variant->effectivePrice() : (float) $product->price;

                return [
                    'key' => (string) $key,
                    'product' => $product,
                    'variant' => $variant,
                    'available' => $available,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'line_total' => $unitPrice * $qty,
                ];
            })
            ->filter()
            ->values();
    }

    public function total(): float
    {
        return (float) $this->items()->sum('line_total');
    }

    private function key(int $productId, ?int $variantId): string
    {
        return $variantId ? "{$productId}-{$variantId}" : (string) $productId;
    }

    private function store(array $items): void
    {
        session()->put(self::SESSION_KEY, $items);
    }
}
