<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The hero banner's words and the shop name are editable in /admin/settings
 * rather than living in the language files, so the owner can change them
 * without a deploy.
 */
class StorefrontWordingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('coming_soon_enabled', 'false');

        Product::create([
            'name_en' => 'A product', 'name_ar' => 'منتج',
            'price' => 10, 'stock' => 1, 'is_active' => true,
        ]);
    }

    private function hero(): string
    {
        $html = $this->get('/')->assertOk()->getContent();
        preg_match('#<section class="relative overflow-hidden rounded-2xl.*?</section>#s', $html, $m);

        return $m[0] ?? '';
    }

    public function test_the_defaults_are_the_words_the_shop_shipped_with(): void
    {
        $hero = $this->hero();

        $this->assertStringContainsString('JorEption', $hero);
        $this->assertStringContainsString('Quality finds at garage-sale prices.', $hero);
        $this->assertStringContainsString('Browse Catalog', $hero);
    }

    public function test_the_owner_can_reword_the_banner(): void
    {
        Setting::set('hero_headline_en', 'Autumn clear-out');
        Setting::set('hero_tagline_en', 'Everything must go.');
        Setting::set('hero_cta_label_en', 'Shop the sale');

        $hero = $this->hero();

        $this->assertStringContainsString('Autumn clear-out', $hero);
        $this->assertStringContainsString('Everything must go.', $hero);
        $this->assertStringContainsString('Shop the sale', $hero);
        $this->assertStringNotContainsString('Quality finds at garage-sale prices.', $hero);
    }

    public function test_clearing_the_tagline_or_the_button_hides_it(): void
    {
        Setting::set('hero_tagline_en', '');
        Setting::set('hero_cta_label_en', '');

        $hero = $this->hero();

        $this->assertStringNotContainsString('Quality finds at garage-sale prices.', $hero);
        $this->assertStringNotContainsString('href="#products"', $hero);
        // The headline must survive — the banner is never wordless.
        $this->assertStringContainsString('JorEption', $hero);
    }

    public function test_an_empty_headline_falls_back_to_the_shop_name(): void
    {
        Setting::set('hero_headline_en', '');
        Setting::set('brand_name_en', 'Amman Finds');

        $this->assertStringContainsString('Amman Finds', $this->hero());
    }

    public function test_the_shop_name_drives_the_header_footer_tab_and_splash(): void
    {
        Setting::set('brand_name_en', 'Amman Finds');

        $html = $this->get('/')->getContent();
        $this->assertStringContainsString('<title>Amman Finds</title>', $html);
        $this->assertSame(2, substr_count($html, '>Amman Finds</span>'), 'header and footer brand');

        Setting::set('coming_soon_enabled', 'true');
        $splash = $this->get('/')->getContent();
        $this->assertStringContainsString('Amman Finds', $splash);
        $this->assertStringContainsString('Coming Soon', $splash);
    }

    public function test_arabic_falls_back_to_english_when_left_blank(): void
    {
        Setting::set('hero_headline_en', 'Autumn clear-out');
        Setting::set('hero_headline_ar', '');

        $this->assertStringContainsString('Autumn clear-out', $this->get('/?lang=ar')->getContent());

        Setting::set('hero_headline_ar', 'تخفيضات الخريف');
        $this->assertStringContainsString('تخفيضات الخريف', $this->get('/?lang=ar')->getContent());
    }

    public function test_the_settings_page_exposes_the_new_fields(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)->get('/admin/settings')
            ->assertOk()
            ->assertSee('Shop name (English)')
            ->assertSee('Headline (English)')
            ->assertSee('Tagline (English)')
            ->assertSee('Button label (English)');
    }
}
