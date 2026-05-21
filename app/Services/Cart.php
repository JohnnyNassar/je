<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class Cart
{
    private const SESSION_KEY = 'cart';

    public function add(int $productId, int $quantity = 1): void
    {
        $items = $this->raw();
        $items[$productId] = ($items[$productId] ?? 0) + $quantity;

        if ($items[$productId] <= 0) {
            unset($items[$productId]);
        }

        $this->store($items);
    }

    public function set(int $productId, int $quantity): void
    {
        $items = $this->raw();

        if ($quantity <= 0) {
            unset($items[$productId]);
        } else {
            $items[$productId] = $quantity;
        }

        $this->store($items);
    }

    public function remove(int $productId): void
    {
        $items = $this->raw();
        unset($items[$productId]);
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
        return array_sum($this->raw());
    }

    public function isEmpty(): bool
    {
        return $this->itemCount() === 0;
    }

    public function items(): Collection
    {
        $raw = $this->raw();

        if (empty($raw)) {
            return collect();
        }

        $products = Product::whereIn('id', array_keys($raw))->get()->keyBy('id');

        return collect($raw)
            ->filter(fn ($qty, $id) => $products->has($id))
            ->map(function ($qty, $id) use ($products) {
                $product = $products->get($id);
                $qty = min($qty, $product->stock);

                return [
                    'product' => $product,
                    'quantity' => $qty,
                    'unit_price' => (float) $product->price,
                    'line_total' => (float) $product->price * $qty,
                ];
            })
            ->values();
    }

    public function total(): float
    {
        return (float) $this->items()->sum('line_total');
    }

    private function store(array $items): void
    {
        session()->put(self::SESSION_KEY, $items);
    }
}
