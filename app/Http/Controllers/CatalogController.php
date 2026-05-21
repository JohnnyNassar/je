<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $categorySlug = trim((string) $request->query('category', ''));

        $categories = Category::active()->orderBy('position')->orderBy('id')->get();
        $activeCategory = $categorySlug ? $categories->firstWhere('slug', $categorySlug) : null;

        $query = Product::active()->orderByDesc('created_at');

        if ($activeCategory) {
            $query->where('category_id', $activeCategory->id);
        }

        if ($q !== '') {
            $like = '%' . $q . '%';
            $query->where(function ($w) use ($like) {
                $w->where('name_en', 'like', $like)
                    ->orWhere('name_ar', 'like', $like)
                    ->orWhere('description_en', 'like', $like)
                    ->orWhere('description_ar', 'like', $like);
            });
        }

        $products = $query->paginate(12)->appends($request->only(['q', 'category']));

        // Featured strip — only on the unfiltered home page
        $featured = ($q === '' && ! $activeCategory)
            ? Product::active()->featured()->orderByDesc('created_at')->take(8)->get()
            : collect();

        return view('catalog.index', compact('products', 'categories', 'activeCategory', 'q', 'featured'));
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        return view('catalog.show', compact('product'));
    }
}
