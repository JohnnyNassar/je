<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('coming_soon_enabled', 'false');
    }

    private function products(int $count, array $attributes = []): void
    {
        foreach (range(1, $count) as $i) {
            Product::create(array_merge([
                'name_en' => "Product {$i}",
                'name_ar' => "منتج {$i}",
                'price' => 10,
                'stock' => 5,
                'is_active' => true,
            ], $attributes));
        }
    }

    public function test_the_pager_shows_page_numbers_on_every_screen_size(): void
    {
        $this->products(40);

        $html = $this->get('/')->getContent();

        // Laravel's stock pager hides the numbered list below `sm` and leaves a
        // phone with nothing but Previous/Next — which is exactly what was
        // reported as "no pagination bar on mobile". There must be no
        // breakpoint-hidden branch left in the pagination markup.
        $this->assertStringContainsString('aria-label="Pagination Navigation"', $html);
        $this->assertStringNotContainsString('sm:hidden', $html);
        $this->assertStringContainsString('aria-label="Go to page 2"', $html);
        $this->assertStringContainsString('Showing', $html);
    }

    public function test_the_pager_marks_the_current_page(): void
    {
        $this->products(40);

        $this->get('/?page=2')->assertSee('aria-current="page"', false);
    }

    public function test_no_pager_when_everything_fits_on_one_page(): void
    {
        $this->products(10);

        $this->get('/')->assertDontSee('Pagination Navigation', false);
    }

    public function test_paging_keeps_the_category_and_the_search_term(): void
    {
        $category = Category::create([
            'name_en' => 'Kitchen', 'name_ar' => 'مطبخ', 'slug' => 'kitchen', 'is_active' => true,
        ]);
        $this->products(40, ['category_id' => $category->id]);

        $html = $this->get('/?category=kitchen')->getContent();
        $this->assertStringContainsString('category=kitchen', $html, 'paging out of a category must stay in it');

        $html = $this->get('/?q=Product')->getContent();
        $this->assertStringContainsString('q=Product', $html, 'paging through results must keep the search');
    }

    public function test_every_product_is_served_exactly_once_across_the_pages(): void
    {
        // Products imported in bulk share a created_at to the second. Ordering
        // on a non-unique column alone lets MySQL return the same row on two
        // pages and drop another, which reads as "pagination is broken".
        $this->products(40, ['created_at' => now()->subDay()]);

        $seen = [];
        for ($page = 1; $page <= 3; $page++) {
            foreach ($this->get("/?page={$page}")->viewData('products') as $product) {
                $seen[] = $product->id;
            }
        }

        $this->assertCount(40, $seen);
        $this->assertCount(40, array_unique($seen), 'a product was served on two different pages');
    }
}
