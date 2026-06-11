<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QuickAddController extends Controller
{
    public function show()
    {
        $categories = \App\Models\Category::active()
            ->orderBy('position')
            ->orderBy('id')
            ->get(['id', 'name_en', 'name_ar']);

        return view('admin.quick-add', compact('categories'));
    }

    public function store(Request $request)
    {
        \Log::info('quick-add POST received', [
            'all' => $request->except(['image']),
            'has_image' => $request->hasFile('image'),
            'image_size' => $request->hasFile('image') ? $request->file('image')->getSize() : null,
            'ajax' => $request->ajax(),
            'wants_json' => $request->wantsJson(),
        ]);

        try {
            $data = $request->validate([
                'image' => ['required', 'image', 'max:8192'],
                'category_id' => ['nullable', 'integer', 'exists:categories,id'],
                'name_en' => ['nullable', 'string', 'max:255'],
                'name_ar' => ['nullable', 'string', 'max:255'],
                'description_en' => ['nullable', 'string', 'max:5000'],
                'description_ar' => ['nullable', 'string', 'max:5000'],
                'price' => ['required', 'numeric', 'min:0'],
                'cost_price' => ['nullable', 'numeric', 'min:0'],
                'compare_at_price' => ['nullable', 'numeric', 'min:0'],
                'stock' => ['required', 'integer', 'min:0'],
                'is_active' => ['nullable', 'boolean'],
                'is_featured' => ['nullable', 'boolean'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('quick-add validation failed', ['errors' => $e->errors()]);
            throw $e;
        }

        if (! ($data['name_en'] ?? null) && ! ($data['name_ar'] ?? null)) {
            \Log::warning('quick-add: no name provided');
            return response()->json([
                'ok' => false,
                'message' => 'Provide at least one name (English or Arabic).',
                'errors' => ['name_en' => ['Required (English or Arabic)']],
            ], 422);
        }

        $path = $request->file('image')->store('products', 'public');
        \Log::info('quick-add: image stored', ['path' => $path]);

        $product = Product::create([
            'category_id' => $data['category_id'] ?? null,
            'name_en' => $data['name_en'] ?: $data['name_ar'],
            'name_ar' => $data['name_ar'] ?: $data['name_en'],
            'description_en' => $data['description_en'] ?? null,
            'description_ar' => $data['description_ar'] ?? null,
            'price' => $data['price'],
            // Cost is gated — ignore any value posted by a user without cost access.
            'cost_price' => auth()->user()?->canViewCost() ? (($data['cost_price'] ?? null) ?: null) : null,
            'compare_at_price' => ($data['compare_at_price'] ?? null) ?: null,
            'stock' => $data['stock'],
            'image_path' => $path,
            'is_active' => $request->boolean('is_active', true),
            'is_featured' => $request->boolean('is_featured', false),
        ]);

        \Log::info('quick-add: product created', ['id' => $product->id]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => (float) $product->price,
                    'image_url' => asset('storage/' . $product->image_path),
                    'public_url' => route('catalog.show', $product),
                ],
            ]);
        }

        return redirect()->route('admin.quick-add')->with('status', 'Product added: ' . $product->name);
    }
}
