<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The brand is written "JorEption" — capital E, as on the bazaar signage
     * and the leasing contract. Every storefront use goes through the single
     * `__('Joreption')` key, so the value in lang/en.json is the one lever.
     */
    public function test_the_storefront_spells_the_brand_with_a_capital_e(): void
    {
        Setting::set('coming_soon_enabled', 'false');

        Product::create([
            'name_en' => 'A product', 'name_ar' => 'منتج',
            'price' => 10, 'stock' => 1, 'is_active' => true,
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        // The hero headline is the banner the owner asked about; the header
        // brand and the footer render the same key.
        preg_match('#<h1[^>]*>(.*?)</h1>#s', $html, $hero);
        $this->assertSame('JorEption', trim($hero[1] ?? ''), 'the banner headline');
        $this->assertStringNotContainsString('>Joreption</span>', $html, 'header and footer brand');
    }
}
