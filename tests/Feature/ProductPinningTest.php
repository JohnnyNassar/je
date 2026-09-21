<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductPinningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Settings default to coming-soon ON (Setting::DEFAULTS), which would
        // serve the splash page instead of the shop for every request here.
        Setting::set("coming_soon_enabled", "false");
    }

    private function product(string $name, array $attributes = []): Product
    {
        // created_at isn't fillable, so it has to be forced on after the fact —
        // otherwise every product in a test shares one timestamp and the
        // newest-first assertions pass or fail on insertion order alone.
        $createdAt = $attributes['created_at'] ?? null;
        unset($attributes['created_at']);

        $product = Product::create(array_merge([
            'name_en' => $name,
            'name_ar' => $name,
            'price' => 10,
            'stock' => 5,
            'is_active' => true,
        ], $attributes));

        if ($createdAt) {
            $product->forceFill(['created_at' => $createdAt])->saveQuietly();
        }

        return $product;
    }

    public function test_pinning_stamps_pinned_at_and_unpinning_clears_it(): void
    {
        $product = $this->product('Grill');
        $this->assertNull($product->pinned_at);

        $product->update(['is_pinned' => true]);
        $this->assertNotNull($product->fresh()->pinned_at);

        $product->update(['is_pinned' => false]);
        $this->assertNull($product->fresh()->pinned_at);
    }

    public function test_saving_a_pinned_product_again_does_not_move_it_to_the_front(): void
    {
        $first = $this->product('First');
        $first->update(['is_pinned' => true]);
        $stampedAt = $first->fresh()->pinned_at;

        $second = $this->product('Second');
        $second->update(['is_pinned' => true]);

        // Editing an unrelated field must not re-stamp the pin, or every price
        // change would silently reshuffle the shop front.
        $first->update(['price' => 99]);

        $this->assertEquals($stampedAt, $first->fresh()->pinned_at);
    }

    public function test_the_cap_is_enforced(): void
    {
        for ($i = 1; $i <= Product::MAX_PINNED; $i++) {
            $this->product("Pinned {$i}")->update(['is_pinned' => true]);
        }

        $this->expectException(ValidationException::class);
        $this->product('One too many')->update(['is_pinned' => true]);
    }

    public function test_the_cap_does_not_count_the_product_being_saved(): void
    {
        $products = collect(range(1, Product::MAX_PINNED))
            ->map(fn (int $i) => tap($this->product("Pinned {$i}"))->update(['is_pinned' => true]));

        // Re-saving one of the five must not trip the cap against itself.
        $products->first()->update(['price' => 42]);

        $this->assertSame(Product::MAX_PINNED, Product::pinned()->count());
    }

    public function test_pinned_products_lead_the_shop_page(): void
    {
        $this->product('Oldest', ['created_at' => now()->subDays(3)]);
        $newest = $this->product('Newest', ['created_at' => now()]);
        $pinned = $this->product('Pinned', ['created_at' => now()->subDays(2)]);
        $pinned->update(['is_pinned' => true]);

        $ids = $this->get('/')->viewData('products')->pluck('id')->all();

        $this->assertSame($pinned->id, $ids[0], 'The pinned product should come first.');
        $this->assertSame($newest->id, $ids[1], 'Everything else stays newest-first.');
    }

    public function test_the_most_recently_pinned_product_leads(): void
    {
        $first = $this->product('First pinned');
        $first->update(['is_pinned' => true]);

        $this->travel(1)->minutes();

        $second = $this->product('Second pinned');
        $second->update(['is_pinned' => true]);

        $ids = $this->get('/')->viewData('products')->pluck('id')->all();

        $this->assertSame([$second->id, $first->id], array_slice($ids, 0, 2));
    }

    public function test_pins_are_ignored_inside_a_category_and_in_search(): void
    {
        $category = Category::create([
            'name_en' => 'Kitchen',
            'name_ar' => 'مطبخ',
            'slug' => 'kitchen',
            'is_active' => true,
        ]);

        $pinned = $this->product('Pinned grill', [
            'category_id' => $category->id,
            'created_at' => now()->subDays(2),
        ]);
        $pinned->update(['is_pinned' => true]);

        $newest = $this->product('Newest grill', [
            'category_id' => $category->id,
            'created_at' => now(),
        ]);

        $inCategory = $this->get('/?category=kitchen')->viewData('products')->pluck('id')->all();
        $this->assertSame($newest->id, $inCategory[0], 'A category listing stays newest-first.');

        $inSearch = $this->get('/?q=grill')->viewData('products')->pluck('id')->all();
        $this->assertSame($newest->id, $inSearch[0], 'Search results stay newest-first.');
    }

    public function test_an_inactive_pinned_product_stays_off_the_shop_page(): void
    {
        $hidden = $this->product('Hidden', ['is_active' => false]);
        $hidden->update(['is_pinned' => true]);
        $visible = $this->product('Visible');

        $ids = $this->get('/')->viewData('products')->pluck('id')->all();

        $this->assertSame([$visible->id], $ids);
        $this->assertTrue($hidden->fresh()->isPinnedButUnavailable());
    }

    public function test_the_shop_page_shows_fifteen_products(): void
    {
        foreach (range(1, 20) as $i) {
            $this->product("Product {$i}");
        }

        $products = $this->get('/')->viewData('products');

        $this->assertCount(15, $products);
        $this->assertSame(20, $products->total());
    }

    public function test_pinned_products_count_towards_the_page_size(): void
    {
        foreach (range(1, 20) as $i) {
            $this->product("Product {$i}");
        }
        Product::orderBy('id')->take(Product::MAX_PINNED)->get()
            ->each(fn (Product $p) => $p->update(['is_pinned' => true]));

        // Pinned products are not an extra row above the grid — they take up
        // five of the fifteen slots.
        $this->assertCount(15, $this->get('/')->viewData('products'));
    }

    public function test_the_admin_product_form_renders_with_the_pin_toggle(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $product = $this->product('Editable');

        // The toggle's helper text and its cap rule are closures Filament only
        // evaluates at render time — a wrong signature surfaces here, not in
        // php -l and not in any of the tests above.
        $this->actingAs($admin)
            ->get('/admin/products/create')
            ->assertOk()
            ->assertSee('Pin to the top of the shop');

        $this->actingAs($admin)
            ->get("/admin/products/{$product->id}/edit")
            ->assertOk()
            ->assertSee('Pin to the top of the shop');
    }
}
